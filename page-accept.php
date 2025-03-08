<?php

if (@$_POST['unlockoutputs'])
	if (no_displayed_error_result($result, multichain('lockunspent', true)))
		output_success_text('All outputs successfully unlocked');

$decoded = null;

function ask_offer_to_assets($askoffer)
{
	$assets = array();

	foreach ($askoffer['assets'] as $asset)
		$assets[$asset['name']] = $asset['qty'];

	if (!count($assets))
		$assets = 0; // to prevent it being converted to empty JSON array instead of object

	return $assets;
}

if (@$_POST['decodeoffer'] || @$_POST['completeoffer']) {
	if (no_displayed_error_result($decoded, multichain('decoderawexchange', $_POST['hex']))) {

		if (@$_POST['completeoffer']) {
			if (no_displayed_error_result($prepare, multichain('preparelockunspentfrom', $_POST['from'], ask_offer_to_assets($decoded['ask'])))) {
				// output_success_text('Exchange successfully prepared using transaction '.$prepare['txid']);

				if (
					no_displayed_error_result($rawexchange, multichain(
						'appendrawexchange',
						$_POST['hex'],
						$prepare['txid'],
						$prepare['vout'],
						ask_offer_to_assets($decoded['offer'])
					))
				) {

					if (no_displayed_error_result($sendtxid, multichain('sendrawtransaction', $rawexchange['hex'])))
						output_success_text('Exchange successfully completed in transaction ' . $sendtxid);
				}
			}
		}
	}
}

$sendaddresses = array();
$usableaddresses = array();
$keymyaddresses = array();
$keyusableassets = array();
$allassets = array();
$haslocked = false;
$getinfo = multichain_getinfo();
$labels = array();

if (no_displayed_error_result($getaddresses, multichain('getaddresses', true))) {
	if (
		no_displayed_error_result(
			$listpermissions,
			multichain('listpermissions', 'send', implode(',', array_get_column($getaddresses, 'address')))
		)
	)
		$sendaddresses = array_get_column($listpermissions, 'address');

	foreach ($getaddresses as $address)
		if ($address['ismine'])
			$keymyaddresses[$address['address']] = true;

	$labels = multichain_labels();

	if (no_displayed_error_result($listassets, multichain('listassets')))
		$allassets = array_get_column($listassets, 'name');
}
?>

<!-- Main content container with responsive grid -->
<div class="grid grid-cols-1 md:grid-cols-12 gap-6">
  
  <!-- Available Balances Panel -->
  <div class="md:col-span-5 space-y-6">
    <div class="bg-white rounded-lg shadow-card overflow-hidden">
      <div class="bg-gradient-primary px-6 py-4">
        <h3 class="text-white text-lg font-mono font-medium m-0 flex items-center">
          <i class="fas fa-wallet mr-3"></i>Available Balances
        </h3>
      </div>
      
      <div class="p-4 space-y-6">
        <?php
        foreach ($sendaddresses as $address) {
          if (no_displayed_error_result($allbalances, multichain('getaddressbalances', $address, 0, true))) {
            if (count($allbalances)) {
              $assetunlocked = array();

              if (no_displayed_error_result($unlockedbalances, multichain('getaddressbalances', $address, 0, false))) {
                if (count($unlockedbalances))
                  $usableaddresses[] = $address;

                foreach ($unlockedbalances as $balance)
                  $assetunlocked[$balance['name']] = $balance['qty'];
              }

              $label = @$labels[$address];
              ?>
              <div class="bg-gray-50 rounded-lg border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 bg-gray-100 border-b border-gray-200">
                  <?php if (isset($label)): ?>
                    <div class="font-medium text-sm text-gray-600">Label: <span class="text-gray-800"><?php echo html($label) ?></span></div>
                  <?php endif; ?>
                  <div class="font-mono text-xs text-gray-600 break-all">
                    <?php echo html($address) ?>
                  </div>
                </div>
                
                <div class="divide-y divide-gray-200">
                  <?php foreach ($allbalances as $balance): 
                    $unlockedqty = floatval($assetunlocked[$balance['name']]);
                    $lockedqty = $balance['qty'] - $unlockedqty;

                    if ($lockedqty > 0)
                      $haslocked = true;
                    if ($unlockedqty > 0)
                      $keyusableassets[$balance['name']] = true;
                  ?>
                    <div class="px-4 py-3 flex justify-between items-center">
                      <span class="font-medium text-sm"><?php echo html($balance['name']) ?></span>
                      <div class="text-right">
                        <span class="font-mono font-medium text-sm"><?php echo html($unlockedqty) ?></span>
                        <?php if ($lockedqty > 0): ?>
                          <span class="font-mono text-xs text-orange-600 ml-1">(<?php echo $lockedqty ?> locked)</span>
                        <?php endif; ?>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
              <?php
            }
          }
        }

        if (!count($sendaddresses)): ?>
          <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded-lg flex items-center">
            <i class="fas fa-exclamation-triangle mr-3"></i>
            <span>No addresses with send permissions found</span>
          </div>
        <?php endif; ?>
        
        <?php if ($haslocked): ?>
          <form method="post" action="./?chain=<?php echo html($_GET['chain']) ?>&page=<?php echo html($_GET['page']) ?>">
            <button type="submit" name="unlockoutputs" class="w-full bg-gradient-button text-white py-3 px-4 rounded-lg hover:shadow-lg transition-all flex items-center justify-center">
              <i class="fas fa-unlock mr-2"></i> Unlock All Outputs
            </button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
  
  <!-- Decode/Complete Offer Panel -->
  <div class="md:col-span-7">
    <div class="bg-white rounded-lg shadow-card overflow-hidden">
      <div class="bg-gradient-primary px-6 py-4">
        <h3 class="text-white text-lg font-mono font-medium m-0 flex items-center">
          <i class="fas fa-exchange-alt mr-3"></i><?php echo is_array($decoded) ? 'Complete Offer' : 'Decode Offer' ?>
        </h3>
      </div>
      
      <div class="p-6">
        <?php if (is_array($decoded)): ?>
          <!-- Complete Offer Form -->
          <form method="post" action="./?chain=<?php echo html($_GET['chain']) ?>&page=<?php echo html($_GET['page']) ?>">
            <!-- Offer Details Panel -->
            <div class="mb-6 bg-gray-50 rounded-lg border border-gray-200 p-4">
              <div class="mb-4 border-b border-gray-200 pb-3">
                <div class="text-sm text-gray-500 font-medium mb-2">OFFER DETAILS</div>
                <div class="space-y-2">
                  <?php foreach ($decoded['offer']['assets'] as $index => $offer): ?>
                    <div class="flex items-center">
                      <div class="w-24 font-medium text-sm"><?php echo $index ? '' : 'Offer:' ?></div>
                      <div class="flex-grow flex items-center">
                        <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded font-medium text-sm">
                          <?php echo html($offer['name']) ?>
                        </span>
                        <span class="mx-2">—</span>
                        <span class="font-mono"><?php echo html($offer['qty']) ?></span>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
              
              <div>
                <div class="text-sm text-gray-500 font-medium mb-2">ASK DETAILS</div>
                <div class="space-y-2">
                  <?php foreach ($decoded['ask']['assets'] as $index => $ask): ?>
                    <div class="flex items-center">
                      <div class="w-24 font-medium text-sm"><?php echo $index ? '' : 'Ask:' ?></div>
                      <div class="flex-grow flex items-center">
                        <span class="bg-indigo-100 text-indigo-800 px-2 py-1 rounded font-medium text-sm">
                          <?php echo html($ask['name']) ?>
                        </span>
                        <span class="mx-2">—</span>
                        <span class="font-mono"><?php echo html($ask['qty']) ?></span>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
            
            <!-- Address Selection -->
            <div class="mb-6">
              <label for="from" class="block font-medium text-gray-700 mb-2">Use Address</label>
              <div class="relative">
                <select class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blockchain-primary focus:border-blockchain-primary rounded-lg appearance-none bg-white border" name="from" id="from">
                  <?php foreach ($usableaddresses as $address): ?>
                    <option value="<?php echo html($address) ?>">
                      <?php echo format_address_html($address, true, $labels) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                  <i class="fas fa-chevron-down"></i>
                </div>
              </div>
              <?php if (!count($usableaddresses)): ?>
                <p class="mt-2 text-sm text-red-600">No addresses with sufficient balances available to complete this offer</p>
              <?php endif; ?>
            </div>
            
            <!-- Offer Hex -->
            <div class="mb-6">
              <label class="block font-medium text-gray-700 mb-2">Offer Hexadecimal</label>
              <textarea class="w-full rounded-lg border border-gray-300 px-4 py-2 bg-gray-50 font-mono text-sm" rows="4" name="hex" readonly><?php echo html($_POST['hex']) ?></textarea>
            </div>
            
            <!-- Submit Button -->
            <div class="flex justify-end">
              <button type="submit" name="completeoffer" class="bg-gradient-button text-white font-medium py-2 px-6 rounded-lg hover:shadow-lg transition-all flex items-center <?php echo count($usableaddresses) ? '' : 'opacity-50 cursor-not-allowed' ?>" <?php echo count($usableaddresses) ? '' : 'disabled' ?>>
                <i class="fas fa-check-circle mr-2"></i> Complete Offer
              </button>
            </div>
          </form>
          
        <?php else: ?>
          <!-- Decode Offer Form -->
          <form method="post" action="./?chain=<?php echo html($_GET['chain']) ?>&page=<?php echo html($_GET['page']) ?>">
            <div class="mb-6">
              <label for="hex" class="block font-medium text-gray-700 mb-2">Offer Hexadecimal</label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                  <i class="fas fa-code"></i>
                </div>
                <textarea class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg font-mono text-sm focus:ring-blockchain-primary focus:border-blockchain-primary" rows="8" name="hex" id="hex" placeholder="Paste the hexadecimal offer data here..."></textarea>
              </div>
              <p class="mt-2 text-xs text-gray-500">Paste the raw hexadecimal exchange offer to decode and verify its contents.</p>
            </div>
            
            <div class="flex justify-end">
              <button type="submit" name="decodeoffer" class="bg-gradient-button text-white font-medium py-2 px-6 rounded-lg hover:shadow-lg transition-all flex items-center">
                <i class="fas fa-search mr-2"></i> Decode Offer
              </button>
            </div>
          </form>
        <?php endif; ?>
      </div>
    </div>
    
    <!-- Quick Help Section -->
    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
      <div class="flex">
        <div class="flex-shrink-0">
          <i class="fas fa-info-circle text-blockchain-primary"></i>
        </div>
        <div class="ml-3">
          <h4 class="text-sm font-medium text-blue-800">About Asset Exchanges</h4>
          <div class="mt-2 text-sm text-blue-700">
            <p>Asset exchanges allow for peer-to-peer atomic swaps of digital assets. To complete an exchange, paste the offer hex from the counterparty, decode it to review details, then complete it if acceptable.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>