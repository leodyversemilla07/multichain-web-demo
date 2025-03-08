<?php

if (@$_POST['unlockoutputs'])
	if (no_displayed_error_result($result, multichain('lockunspent', true)))
		output_success_text('All outputs successfully unlocked');

if (@$_POST['sendasset']) {
	if (strlen($_POST['metadata']))
		$success = no_displayed_error_result($sendtxid, multichain(
			'sendwithmetadatafrom',
			$_POST['from'],
			$_POST['to'],
			array($_POST['asset'] => floatval($_POST['qty'])),
			bin2hex($_POST['metadata'])
		));
	else
		$success = no_displayed_error_result($sendtxid, multichain(
			'sendassetfrom',
			$_POST['from'],
			$_POST['to'],
			$_POST['asset'],
			floatval($_POST['qty'])
		));

	if ($success)
		output_success_text('Asset successfully sent in transaction ' . $sendtxid);
}
?>

<!-- Main Content Container -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <!-- Left Column: Available Balances -->
    <div class="lg:col-span-5">
        <div class="bg-white rounded-lg shadow-card border border-gray-100 overflow-hidden">
            <!-- Section Header -->
            <div class="bg-gradient-to-r from-gray-100 to-gray-50 px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-800 flex items-center m-0">
                    <i class="fas fa-wallet text-blockchain-primary mr-3"></i>
                    Available Balances
                </h3>
            </div>

            <!-- Section Content -->
            <div class="p-4">
                <?php
                $sendaddresses = array();
                $usableaddresses = array();
                $keymyaddresses = array();
                $keyusableassets = array();
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

                    if (no_displayed_error_result($listpermissions, multichain('listpermissions', 'receive')))
                        $receiveaddresses = array_get_column($listpermissions, 'address');

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
                                
                                // Card for each address balance
                                ?>
                                <div class="mb-6 bg-white rounded-lg border <?php echo ($address == @$getnewaddress) ? 'border-green-300 shadow-lg' : 'border-gray-200 shadow' ?>">
                                    <!-- Address header section -->
                                    <div class="bg-gray-50 p-3 rounded-t-lg border-b border-gray-200">
                                        <?php if (isset($label)) { ?>
                                            <div class="mb-2">
                                                <span class="inline-block px-2 py-1 bg-gray-200 text-gray-700 rounded-md text-xs font-medium">
                                                    <?php echo html($label) ?>
                                                </span>
                                            </div>
                                        <?php } ?>
                                        <div class="flex items-center">
                                            <i class="fas fa-address-card text-blockchain-primary mr-2"></i>
                                            <div class="hash-value w-full text-xs">
                                                <?php echo html($address) ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Balance items -->
                                    <div class="p-3">
                                        <ul class="divide-y divide-gray-100">
                                            <?php
                                            foreach ($allbalances as $balance) {
                                                $unlockedqty = floatval(@$assetunlocked[$balance['name']]);
                                                $lockedqty = $balance['qty'] - $unlockedqty;

                                                if ($lockedqty > 0)
                                                    $haslocked = true;
                                                if ($unlockedqty > 0)
                                                    $keyusableassets[$balance['name']] = true;
                                                ?>
                                                <li class="py-2 flex justify-between items-center">
                                                    <div class="font-medium text-gray-700">
                                                        <i class="fas fa-coins text-blockchain-primary mr-2"></i>
                                                        <?php echo html($balance['name']) ?>
                                                    </div>
                                                    <div>
                                                        <span class="blockchain-data text-gray-900"><?php echo html($unlockedqty) ?></span>
                                                        <?php if ($lockedqty > 0) { ?>
                                                            <span class="ml-1 text-yellow-600 text-sm">
                                                                <i class="fas fa-lock text-xs"></i>
                                                                <?php echo $lockedqty ?>
                                                            </span>
                                                        <?php } ?>
                                                    </div>
                                                </li>
                                            <?php } ?>
                                        </ul>
                                    </div>
                                </div>
                            <?php
                            }
                        }
                    }
                }

                if ($haslocked) {
                ?>
                    <form method="post" action="./?chain=<?php echo html($_GET['chain']) ?>&page=<?php echo html($_GET['page']) ?>" class="mt-5">
                        <button type="submit" name="unlockoutputs" class="w-full flex items-center justify-center px-4 py-2 bg-yellow-100 text-yellow-700 border border-yellow-300 rounded hover:bg-yellow-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 transition-colors">
                            <i class="fas fa-unlock-alt mr-2"></i>
                            Unlock all outputs
                        </button>
                    </form>
                <?php
                } else {
                    // Show empty state if no locked outputs
                    if (empty($usableaddresses)) {
                    ?>
                        <div class="text-center py-10">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 text-gray-400 mb-4">
                                <i class="fas fa-wallet text-2xl"></i>
                            </div>
                            <p class="text-gray-500">No available balances found.</p>
                        </div>
                    <?php
                    }
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Right Column: Send Asset Form -->
    <div class="lg:col-span-7">
        <div class="bg-white rounded-lg shadow-card border border-gray-100 overflow-hidden">
            <!-- Form Header -->
            <div class="bg-gradient-to-r from-blockchain-light to-blockchain-primary px-6 py-4">
                <h3 class="text-lg font-medium text-white flex items-center m-0">
                    <i class="fas fa-paper-plane mr-3"></i>
                    Send Asset
                </h3>
            </div>

            <!-- Form Content -->
            <div class="p-6">
                <?php if (empty($usableaddresses)) { ?>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4 mb-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-circle text-yellow-400"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800">No addresses available for sending</h3>
                                <div class="mt-2 text-sm text-yellow-700">
                                    <p>You need at least one address with unlocked assets to send.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>

                <form method="post" action="./?chain=<?php echo html($_GET['chain']) ?>&page=<?php echo html($_GET['page']) ?>" x-data="{ hasMetadata: false }">
                    <!-- From Address Field -->
                    <div class="mb-6">
                        <label for="from" class="block text-sm font-medium text-gray-700 mb-1">From address:</label>
                        <div class="relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                            <select name="from" id="from" class="pl-10 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blockchain-primary focus:border-blockchain-primary" <?php echo empty($usableaddresses) ? 'disabled' : ''; ?>>
                                <?php foreach ($usableaddresses as $address) { ?>
                                    <option value="<?php echo html($address) ?>">
                                        <?php echo format_address_html($address, true, $labels) ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>

                    <!-- Asset Name Field -->
                    <div class="mb-6">
                        <label for="asset" class="block text-sm font-medium text-gray-700 mb-1">Asset name:</label>
                        <div class="relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-coins text-gray-400"></i>
                            </div>
                            <select name="asset" id="asset" class="pl-10 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blockchain-primary focus:border-blockchain-primary" <?php echo empty($keyusableassets) ? 'disabled' : ''; ?>>
                                <?php foreach ($keyusableassets as $asset => $dummy) { ?>
                                    <option value="<?php echo html($asset) ?>"><?php echo html($asset) ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>

                    <!-- To Address Field -->
                    <div class="mb-6">
                        <label for="to" class="block text-sm font-medium text-gray-700 mb-1">To address:</label>
                        <div class="relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user-plus text-gray-400"></i>
                            </div>
                            <select name="to" id="to" class="pl-10 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blockchain-primary focus:border-blockchain-primary" <?php echo empty($receiveaddresses) ? 'disabled' : ''; ?>>
                                <?php foreach ($receiveaddresses as $address) {
                                    if ($address == $getinfo['burnaddress'])
                                        continue;
                                    ?>
                                    <option value="<?php echo html($address) ?>">
                                        <?php echo format_address_html($address, @$keymyaddresses[$address], $labels) ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>

                    <!-- Quantity Field -->
                    <div class="mb-6">
                        <label for="qty" class="block text-sm font-medium text-gray-700 mb-1">Quantity:</label>
                        <div class="relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-balance-scale text-gray-400"></i>
                            </div>
                            <input type="text" name="qty" id="qty" class="pl-10 block w-full rounded-md border-gray-300 shadow-sm focus:ring-blockchain-primary focus:border-blockchain-primary" placeholder="0.0" <?php echo empty($usableaddresses) ? 'disabled' : ''; ?>>
                        </div>
                    </div>

                    <!-- Metadata Toggle -->
                    <div class="bg-gray-50 rounded-md p-3 mb-6">
                        <div class="flex items-center">
                            <button type="button" @click="hasMetadata = !hasMetadata" class="inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blockchain-primary">
                                <i class="fas" :class="hasMetadata ? 'fa-minus' : 'fa-plus'"></i>
                                <span class="ml-2">Metadata</span>
                            </button>
                            <span class="ml-3 text-xs text-gray-500">Optional data to include with the transaction</span>
                        </div>
                        
                        <!-- Metadata Textarea (Shown conditionally) -->
                        <div x-show="hasMetadata" x-cloak class="mt-3">
                            <label for="metadata" class="block text-sm font-medium text-gray-700 mb-1">Transaction Metadata:</label>
                            <textarea name="metadata" id="metadata" rows="3" class="block w-full rounded-md border-gray-300 shadow-sm focus:ring-blockchain-primary focus:border-blockchain-primary" placeholder="Enter metadata (text or JSON)"><?php echo @$_POST['metadata'] ?></textarea>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end">
                        <button type="submit" name="sendasset" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-gradient-button hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blockchain-primary transition-all" <?php echo (empty($usableaddresses) || empty($keyusableassets)) ? 'disabled' : ''; ?>>
                            <i class="fas fa-paper-plane mr-2"></i>
                            Send Asset
                        </button>
                    </div>
                </form>
                
                <!-- Transaction Guide -->
                <div class="mt-8 border-t border-gray-200 pt-6">
                    <h4 class="text-sm font-medium text-gray-700 mb-3">How to send assets</h4>
                    <ol class="list-decimal list-inside space-y-2 text-sm text-gray-600">
                        <li>Select the source address containing your assets</li>
                        <li>Choose which asset you want to send</li>
                        <li>Select the recipient address</li>
                        <li>Enter the quantity of assets to send</li>
                        <li>Add optional metadata if needed</li>
                        <li>Click "Send Asset" to complete the transaction</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>