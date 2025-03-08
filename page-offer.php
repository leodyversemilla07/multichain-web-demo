<?php

if (@$_POST['unlockoutputs'])
	if (no_displayed_error_result($result, multichain('lockunspent', true)))
		output_success_text('All outputs successfully unlocked');

if (@$_POST['createoffer']) {
	if (
		no_displayed_error_result($prepare, multichain(
			'preparelockunspentfrom',
			$_POST['from'],
			array($_POST['offerasset'] => floatval($_POST['offerqty']))
		))
	) {

		if (
			no_displayed_error_result($rawexchange, multichain(
				'createrawexchange',
				$prepare['txid'],
				$prepare['vout'],
				array($_POST['askasset'] => floatval($_POST['askqty']))
			))
		) {
            // Success notification with improved styling
            ?>
            <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded-md shadow-sm">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-500"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">
                            Offer successfully prepared using transaction <?php echo $prepare['txid']; ?>
                        </p>
                        <p class="text-sm text-green-700 mt-1">
                            Please copy the raw offer below.
                        </p>
                    </div>
                </div>
            </div>

            <div class="mb-6 bg-gray-50 p-4 border border-gray-200 rounded-md">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm font-medium text-gray-700">Raw Exchange Data</span>
                    <button onclick="copyToClipboard('raw-exchange-data')" class="text-blockchain-primary hover:text-blockchain-dark text-sm flex items-center">
                        <i class="fas fa-copy mr-1"></i> Copy
                    </button>
                </div>
                <pre id="raw-exchange-data" class="blockchain-data text-sm bg-white p-4 border border-gray-200 rounded overflow-auto max-h-48"><?php echo html($rawexchange); ?></pre>
            </div>
            
            <script>
            function copyToClipboard(elementId) {
                const el = document.getElementById(elementId);
                const textArea = document.createElement('textarea');
                textArea.value = el.textContent;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                
                // Show temporary copied notification
                const button = event.currentTarget;
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check mr-1"></i> Copied!';
                setTimeout(() => {
                    button.innerHTML = originalText;
                }, 2000);
            }
            </script>
            <?php
		}
	}
}

// Fetch necessary data
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

    // Process addresses and balances
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

                foreach ($allbalances as $balance) {
                    $unlockedqty = floatval($assetunlocked[$balance['name']]);
                    $lockedqty = $balance['qty'] - $unlockedqty;

                    if ($lockedqty > 0)
                        $haslocked = true;
                    if ($unlockedqty > 0)
                        $keyusableassets[$balance['name']] = true;
                }
            }
        }
    }
}
?>

<!-- Main Content -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
    <!-- Available Balances Section -->
    <div class="lg:col-span-5">
        <div class="bg-white rounded-lg shadow-card overflow-hidden border border-gray-100 h-full">
            <!-- Section Header -->
            <div class="bg-gradient-primary px-6 py-4">
                <h3 class="text-lg font-semibold text-white m-0 flex items-center">
                    <i class="fas fa-wallet mr-3"></i> Available Balances
                </h3>
            </div>
            
            <div class="p-6">
                <?php if ($haslocked): ?>
                <form method="post" action="./?chain=<?php echo html($_GET['chain']) ?>&page=<?php echo html($_GET['page']) ?>" class="mb-6">
                    <button type="submit" name="unlockoutputs" class="inline-flex items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-medium text-white hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 shadow-sm transition-colors">
                        <i class="fas fa-unlock mr-2"></i> Unlock all outputs
                    </button>
                </form>
                <?php endif; ?>
                
                <div class="space-y-6">
                    <?php
                    $addressesToShow = array();
                    
                    foreach ($sendaddresses as $address) {
                        if (no_displayed_error_result($allbalances, multichain('getaddressbalances', $address, 0, true))) {
                            if (count($allbalances)) {
                                $assetunlocked = array();
                                $label = @$labels[$address];
                                $addressData = array('address' => $address, 'label' => $label, 'balances' => array());
                                
                                if (no_displayed_error_result($unlockedbalances, multichain('getaddressbalances', $address, 0, false))) {
                                    foreach ($unlockedbalances as $balance)
                                        $assetunlocked[$balance['name']] = $balance['qty'];
                                }
                                
                                foreach ($allbalances as $balance) {
                                    $unlockedqty = floatval($assetunlocked[$balance['name']]);
                                    $lockedqty = $balance['qty'] - $unlockedqty;
                                    
                                    $addressData['balances'][] = array(
                                        'name' => $balance['name'],
                                        'unlockedqty' => $unlockedqty,
                                        'lockedqty' => $lockedqty
                                    );
                                }
                                
                                $addressesToShow[] = $addressData;
                            }
                        }
                    }
                    
                    if (count($addressesToShow) > 0) {
                        foreach ($addressesToShow as $addr): ?>
                            <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                                <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                                    <div class="flex justify-between items-center">
                                        <?php if (isset($addr['label']) && strlen($addr['label'])): ?>
                                            <h4 class="text-sm font-medium text-gray-900 m-0">
                                                <?php echo html($addr['label']); ?>
                                            </h4>
                                        <?php else: ?>
                                            <h4 class="text-sm font-medium text-gray-500 m-0">Unlabeled Address</h4>
                                        <?php endif; ?>
                                        
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            <i class="fas fa-key mr-1"></i> 
                                            Send Permission
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="px-4 py-2 text-xs font-mono text-gray-500 border-b border-gray-200 bg-gray-50">
                                    <?php echo html($addr['address']); ?>
                                </div>
                                
                                <div>
                                    <?php foreach ($addr['balances'] as $index => $balance): 
                                        $even = $index % 2 === 0;
                                    ?>
                                        <div class="flex justify-between px-4 py-3 <?php echo $even ? 'bg-white' : 'bg-gray-50'; ?> border-b border-gray-200 last:border-b-0">
                                            <div class="font-medium text-gray-700"><?php echo html($balance['name']); ?></div>
                                            <div class="text-right">
                                                <span class="font-mono text-gray-900"><?php echo html($balance['unlockedqty']); ?></span>
                                                <?php if ($balance['lockedqty'] > 0): ?>
                                                    <span class="ml-1 text-yellow-600 text-xs">
                                                        (<?php echo html($balance['lockedqty']); ?> locked)
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach;
                    } else {
                        ?>
                        <div class="bg-gray-50 rounded-lg p-6 text-center border border-gray-200">
                            <div class="inline-block p-3 bg-gray-100 rounded-full mb-4">
                                <i class="fas fa-info-circle text-gray-400 text-xl"></i>
                            </div>
                            <h4 class="text-gray-500 font-medium mb-2">No Available Balances</h4>
                            <p class="text-gray-500 text-sm">No addresses with send permissions and balances were found.</p>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Create Offer Section -->
    <div class="lg:col-span-7">
        <div class="bg-white rounded-lg shadow-card overflow-hidden border border-gray-100">
            <!-- Section Header -->
            <div class="bg-gradient-primary px-6 py-4">
                <h3 class="text-lg font-semibold text-white m-0 flex items-center">
                    <i class="fas fa-handshake mr-3"></i> Create Exchange Offer
                </h3>
            </div>
            
            <div class="p-6">
                <?php if (count($usableaddresses) && count($keyusableassets)): ?>
                    <form method="post" action="./?chain=<?php echo html($_GET['chain']) ?>&page=<?php echo html($_GET['page']) ?>" x-data="{ offerAsset: '', askAsset: '' }">
                        <!-- Card with offer direction visualization -->
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 mb-6">
                            <div class="flex flex-col md:flex-row items-center justify-center">
                                <div class="bg-white px-4 py-3 rounded-lg shadow-sm border border-gray-200 flex-shrink-0 text-center mb-3 md:mb-0">
                                    <div class="text-sm text-gray-500">You're offering</div>
                                    <div class="font-mono font-bold text-blockchain-primary text-lg" x-text="offerAsset || '---'"></div>
                                </div>
                                
                                <div class="flex items-center justify-center px-6 md:px-10">
                                    <div class="h-0.5 w-6 md:w-12 bg-gray-300"></div>
                                    <div class="mx-3">
                                        <i class="fas fa-exchange-alt text-gray-400"></i>
                                    </div>
                                    <div class="h-0.5 w-6 md:w-12 bg-gray-300"></div>
                                </div>
                                
                                <div class="bg-white px-4 py-3 rounded-lg shadow-sm border border-gray-200 flex-shrink-0 text-center">
                                    <div class="text-sm text-gray-500">You're asking for</div>
                                    <div class="font-mono font-bold text-blockchain-primary text-lg" x-text="askAsset || '---'"></div>
                                </div>
                            </div>
                        </div>
                    
                        <div class="space-y-5">
                            <!-- From Address Field -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                                <label for="from" class="block text-sm font-medium text-gray-700">From address</label>
                                <div class="md:col-span-2">
                                    <select name="from" id="from" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blockchain-primary focus:border-blockchain-primary rounded-md shadow-sm">
                                        <?php foreach ($usableaddresses as $address): ?>
                                            <option value="<?php echo html($address); ?>">
                                                <?php 
                                                $label = @$labels[$address];
                                                if (isset($label) && strlen($label))
                                                    echo html($label) . ' - '; 
                                                echo html($address);
                                                ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Offer Asset Section with colored background -->
                            <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                                <h4 class="text-sm font-medium text-blue-800 mb-3 flex items-center">
                                    <i class="fas fa-arrow-right mr-2"></i> Offer Details
                                </h4>
                                
                                <div class="space-y-4">
                                    <!-- Offer Asset Field -->
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                                        <label for="offerasset" class="block text-sm font-medium text-gray-700">Asset to offer</label>
                                        <div class="md:col-span-2">
                                            <select name="offerasset" id="offerasset" x-model="offerAsset" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blockchain-primary focus:border-blockchain-primary rounded-md shadow-sm">
                                                <option value="">Select an asset</option>
                                                <?php foreach ($keyusableassets as $asset => $dummy): ?>
                                                    <option value="<?php echo html($asset); ?>"><?php echo html($asset); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <!-- Offer Quantity Field -->
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                                        <label for="offerqty" class="block text-sm font-medium text-gray-700">Quantity to offer</label>
                                        <div class="md:col-span-2">
                                            <div class="mt-1 relative rounded-md shadow-sm">
                                                <input type="number" step="any" name="offerqty" id="offerqty" placeholder="0.0" required 
                                                    class="focus:ring-blockchain-primary focus:border-blockchain-primary block w-full pl-3 pr-12 sm:text-sm border-gray-300 rounded-md">
                                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                    <span class="text-gray-500 sm:text-sm" x-text="offerAsset"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Ask Asset Section with colored background -->
                            <div class="bg-green-50 p-4 rounded-lg border border-green-100">
                                <h4 class="text-sm font-medium text-green-800 mb-3 flex items-center">
                                    <i class="fas fa-arrow-left mr-2"></i> Ask Details
                                </h4>
                                
                                <div class="space-y-4">
                                    <!-- Ask Asset Field -->
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                                        <label for="askasset" class="block text-sm font-medium text-gray-700">Asset to receive</label>
                                        <div class="md:col-span-2">
                                            <select name="askasset" id="askasset" x-model="askAsset" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blockchain-primary focus:border-blockchain-primary rounded-md shadow-sm">
                                                <option value="">Select an asset</option>
                                                <?php foreach ($allassets as $asset): ?>
                                                    <option value="<?php echo html($asset); ?>"><?php echo html($asset); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <!-- Ask Quantity Field -->
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                                        <label for="askqty" class="block text-sm font-medium text-gray-700">Quantity to receive</label>
                                        <div class="md:col-span-2">
                                            <div class="mt-1 relative rounded-md shadow-sm">
                                                <input type="number" step="any" name="askqty" id="askqty" placeholder="0.0" required
                                                    class="focus:ring-blockchain-primary focus:border-blockchain-primary block w-full pl-3 pr-12 sm:text-sm border-gray-300 rounded-md">
                                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                    <span class="text-gray-500 sm:text-sm" x-text="askAsset"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Exchange rate calculation (optional - using Alpine.js) -->
                        <div x-data="{ 
                            offerQty: 0, 
                            askQty: 0,
                            get rate() {
                                if (this.offerQty > 0 && this.askQty > 0) {
                                    return (this.askQty / this.offerQty).toFixed(8);
                                }
                                return '0';
                            }
                        }" x-init="() => {
                            $watch('offerQty', value => { offerQty = parseFloat(value) || 0 });
                            $watch('askQty', value => { askQty = parseFloat(value) || 0 });
                            
                            document.getElementById('offerqty').addEventListener('input', (e) => {
                                offerQty = parseFloat(e.target.value) || 0;
                            });
                            
                            document.getElementById('askqty').addEventListener('input', (e) => {
                                askQty = parseFloat(e.target.value) || 0;
                            });
                        }" class="mt-6 bg-gray-50 p-4 rounded-lg border border-gray-200 flex items-center justify-between">
                            <div class="text-sm text-gray-500">Exchange rate:</div>
                            <div class="font-mono">
                                <span x-text="rate"></span> 
                                <span x-text="askAsset"></span> per <span x-text="offerAsset"></span>
                            </div>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="mt-8 flex justify-end">
                            <button type="submit" name="createoffer" class="inline-flex items-center px-6 py-3 border border-transparent rounded-md shadow-sm text-base font-medium text-white bg-gradient-button hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blockchain-primary transition-all">
                                <i class="fas fa-handshake mr-2"></i>
                                Create Exchange Offer
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="bg-yellow-50 p-6 rounded-lg border border-yellow-200 text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-yellow-100 text-yellow-600 mb-4">
                            <i class="fas fa-exclamation-triangle text-2xl"></i>
                        </div>
                        <h4 class="text-lg font-medium text-yellow-800 mb-2">Cannot Create Offers</h4>
                        <p class="text-yellow-700">
                            <?php if (!count($usableaddresses)): ?>
                                No addresses with send permissions and unlocked balances available.
                            <?php elseif (!count($keyusableassets)): ?>
                                No unlocked assets available to offer.
                            <?php else: ?>
                                Missing required resources to create an exchange offer.
                            <?php endif; ?>
                        </p>
                        <div class="mt-4">
                            <a href="./?chain=<?php echo html($_GET['chain']) ?>" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-blockchain-primary bg-blue-50 hover:bg-blue-100">
                                <i class="fas fa-arrow-left mr-2"></i> Return to Dashboard
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Instructions Panel -->
        <div class="mt-6 bg-white rounded-lg shadow-card overflow-hidden border border-gray-100">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                <h4 class="text-base font-medium text-gray-700 m-0 flex items-center">
                    <i class="fas fa-info-circle text-blockchain-primary mr-2"></i> About Exchange Offers
                </h4>
            </div>
            
            <div class="p-6">
                <div class="prose prose-sm max-w-none text-gray-500">
                    <p><strong>Exchange offers</strong> allow you to trade assets with other users in a secure, atomic way.</p>
                    <ul>
                        <li>Select the address containing the assets you want to offer</li>
                        <li>Choose which asset and how much you're willing to exchange</li>
                        <li>Specify which asset and quantity you want in return</li>
                        <li>After creating an offer, share the generated raw exchange data with your trading partner</li>
                        <li>The exchange will only happen if both parties fulfill the exact terms</li>
                    </ul>
                    <p class="text-xs bg-blue-50 p-3 rounded border border-blue-100">
                        <i class="fas fa-lock mr-1"></i> <strong>Note:</strong> Creating an offer will temporarily lock the offered assets until the offer expires or is accepted.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>