<?php

define('const_max_for_entities', 3);

require_once 'functions-filter.php';

$success = false; // set default value

$keyforentities = array();
for ($forentity = 1; $forentity <= const_max_for_entities; $forentity++)
	if (strlen(@$_POST['for' . $forentity]))
		$keyforentities[$_POST['for' . $forentity]] = true;

$restrictions = count($keyforentities) ? array('for' => array_keys($keyforentities)) : false;

if (@$_POST['testtxfiltercode']) {
	if (no_displayed_error_result($testtxfilter, multichain('testtxfilter', false, $_POST['code']))) {
		if ($testtxfilter['compiled'])
			output_success_text('Filter code successfully compiled');
		else
			output_error_text('Filter code failed to compile:' . "\n" . $testtxfilter['reason']);
	}
}

if (@$_POST['createtxfilter']) {
	$success = no_displayed_error_result($createtxid, multichain(
		'createfrom',
		$_POST['createfrom'],
		'txfilter',
		$_POST['name'],
		$restrictions,
		$_POST['code']
	));

	if ($success)
		output_success_text('Filter successfully created in transaction ' . $createtxid);
}

$sendrawtx = null;

if (@$_POST['testtxfiltersend'])
	if (
		no_displayed_error_result($createrawsendfrom, multichain(
			'createrawsendfrom',
			$_POST['sendfrom'],
			array($_POST['to'] => array($_POST['asset'] => floatval($_POST['qty']))),
			array(),
			'sign'
		))
	) {
		$sendrawtx = $createrawsendfrom['hex'];
		$showcallbacks = $_POST['sendcallbacks'];
	}

if (@$_POST['testtxfilterraw']) {
	$sendrawtx = trim($_POST['rawtx']);
	$showcallbacks = $_POST['rawcallbacks'];
}

if (isset($sendrawtx)) {
	if (
		no_displayed_error_result($testtxfilter, multichain_with_raw(
			$testtxfilterraw,
			'testtxfilter',
			$restrictions,
			$_POST['code'],
			$sendrawtx
		))
	) {

		if ($testtxfilter['compiled']) {
			$suffix = ' (time taken ' . number_format($testtxfilter['time'], 6) . ' seconds)';

			if ($testtxfilter['passed'])
				output_success_text('Filter code allowed this transaction' . $suffix);
			else
				output_error_text('Filter code blocked this transaction with the reason: ' . $suffix . "\n" . $testtxfilter['reason']);

			if ($showcallbacks)
				output_filter_test_callbacks($testtxfilterraw);

		} else
			output_error_text('Filter code failed to compile:' . "\n" . $testtxfilter['reason']);
	}
}

$labels = multichain_labels();

if (no_displayed_error_result($getaddresses, multichain('getaddresses', true))) {
	$keymyaddresses = array();
	$sendaddresses = array();
	$receiveaddresses = array();
	$adminaddresses = array();
	$admincreateaddresses = array();

	foreach ($getaddresses as $index => $address)
		if ($address['ismine'])
			$keymyaddresses[$address['address']] = true;
		else
			unset($getaddresses[$index]);

	if (
		no_displayed_error_result(
			$listpermissions,
			multichain('listpermissions', 'send', implode(',', array_get_column($getaddresses, 'address')))
		)
	)
		$sendaddresses = array_get_column($listpermissions, 'address');

	if (no_displayed_error_result($listpermissions, multichain('listpermissions', 'receive')))
		$receiveaddresses = array_get_column($listpermissions, 'address');

	if (
		no_displayed_error_result(
			$listpermissions,
			multichain('listpermissions', 'admin', implode(',', array_get_column($getaddresses, 'address')))
		)
	)
		$adminaddresses = array_unique(array_get_column($listpermissions, 'address'));

	if (
		no_displayed_error_result(
			$listpermissions,
			multichain('listpermissions', 'create', implode(',', $adminaddresses))
		)
	)
		$admincreateaddresses = array_unique(array_get_column($listpermissions, 'address'));
}

no_displayed_error_result($listassets, multichain('listassets'));
no_displayed_error_result($liststreams, multichain('liststreams'));

$getinfo = multichain_getinfo();

$usableassets = array();
if (no_displayed_error_result($gettotalbalances, multichain('gettotalbalances')))
	$usableassets = array_get_column($gettotalbalances, 'name');
?>

<!-- Transaction Filters Dashboard -->
<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <!-- Left Column for Existing Filters -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow-card overflow-hidden mb-5">
            <div class="bg-gradient-primary px-4 py-4 text-white">
                <div class="flex items-center">
                    <i class="fas fa-filter mr-3 text-xl"></i>
                    <h3 class="font-mono text-lg font-medium m-0">Transaction Filters</h3>
                </div>
            </div>
            
            <?php if (no_displayed_error_result($listtxfilters, multichain('listtxfilters', '*', true))): ?>
                <div class="divide-y divide-gray-100">
                    <?php foreach ($listtxfilters as $filter): 
                        $name = $filter['name'];
                    ?>
                        <div class="p-4 <?php echo ($success && ($name == @$_POST['name'])) ? 'bg-green-50' : '' ?>">
                            <div class="flex justify-between items-center mb-2">
                                <div class="font-medium text-gray-800 flex items-center">
                                    <i class="fas fa-tag mr-2 text-blockchain-primary text-sm"></i>
                                    <?php echo html($name) ?>
                                </div>
                                <div class="text-xs px-2 py-1 rounded-full 
                                    <?php if ($filter['approved']): ?>
                                        bg-green-100 text-green-700
                                    <?php else: ?>
                                        bg-yellow-100 text-yellow-700
                                    <?php endif; ?>
                                ">
                                    <?php echo $filter['approved'] ? 'Approved' : 'Pending' ?>
                                </div>
                            </div>
                            
                            <?php if (count($filter['for'])): ?>
                                <div class="my-2 flex items-start">
                                    <span class="text-xs font-medium text-gray-500 mr-2 mt-1">Only for:</span>
                                    <div class="flex flex-wrap gap-1">
                                        <?php foreach ($filter['for'] as $filterfor): ?>
                                            <span class="text-xs bg-gray-100 text-gray-700 rounded px-2 py-1">
                                                <?php echo html($filterfor['name']) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="mt-3 flex flex-col space-y-2">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600">Code:</span>
                                    <a href="./filter-code.php?chain=<?php echo html($_GET['chain']) ?>&txid=<?php echo html($filter['createtxid']) ?>" 
                                       class="text-blockchain-primary hover:underline flex items-center">
                                        <?php echo number_format($filter['codelength']) ?> bytes 
                                        <i class="fas fa-external-link-alt ml-1 text-xs"></i>
                                    </a>
                                </div>
                                
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600">Language:</span>
                                    <span class="font-mono"><?php echo html(ucfirst($filter['language'])) ?></span>
                                </div>
                                
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600">Status:</span>
                                    <div class="flex items-center">
                                        <?php output_txfilter_status($filter) ?>
                                        <a href="./?chain=<?php echo html($chain) ?>&page=approve&txfilter=<?php echo html($filter['createtxid']) ?>" 
                                           class="ml-2 text-blockchain-primary hover:underline text-xs">
                                            change
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (count($listtxfilters) === 0): ?>
                    <div class="p-6 text-center">
                        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-blue-50 text-blockchain-primary mb-3">
                            <i class="fas fa-info-circle text-lg"></i>
                        </div>
                        <p class="text-gray-500">No transaction filters found.</p>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="p-6 text-center">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-red-50 text-red-500 mb-3">
                        <i class="fas fa-exclamation-circle text-lg"></i>
                    </div>
                    <p class="text-gray-500">Error loading transaction filters.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right Column for Filter Creation/Testing -->
    <div class="lg:col-span-3">
        <div class="bg-white rounded-lg shadow-card overflow-hidden">
            <div class="bg-gradient-header px-4 py-4 text-white">
                <div class="flex items-center">
                    <i class="fas fa-code mr-3 text-xl"></i>
                    <h3 class="font-mono text-lg font-medium m-0">Test or Create Transaction Filter</h3>
                </div>
            </div>
            
            <div class="p-4 md:p-6">
                <form method="post" action="./?chain=<?php echo html($_GET['chain']) ?>&page=<?php echo html($_GET['page']) ?>">
                    <!-- Filter Code Section -->
                    <div class="mb-8 border-b border-gray-200 pb-8">
                        <h4 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-code-branch text-blockchain-primary mr-2"></i>
                            Filter Code
                        </h4>
                        
                        <div class="mb-5">
                            <label for="code" class="block text-sm font-medium text-gray-700 mb-2">Filter JavaScript Code:</label>
                            <div class="relative">
                                <div class="absolute top-0 right-0 z-10 bg-gray-50 rounded-bl rounded-tr px-3 py-1 text-xs text-gray-500 font-mono">JavaScript</div>
                                <textarea class="form-control w-full h-64 font-mono bg-gray-50 border border-gray-300 rounded-md px-3 py-2 focus:ring-blockchain-primary focus:border-blockchain-primary" 
                                          name="code" id="code"><?php if (strlen(@$_POST['code']))
                                              echo html($_POST['code']);
                                          else {
                                          ?>function filtertransaction()
{
    var tx=getfiltertransaction();

    if (tx.vout.length<1)
        return "One output required";
}<?php } ?></textarea>
                            </div>
                        </div>
                        
                        <div class="mb-5">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Only Apply Filter If Transaction Uses:</label>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <?php
                                $entities = array();
                                
                                foreach ($listassets as $asset)
                                    $entities[$asset['issuetxid']] = $asset['name'];
                                
                                foreach ($liststreams as $stream)
                                    $entities[$stream['createtxid']] = $stream['name'];
                                
                                for ($forentity = 1; $forentity <= const_max_for_entities; $forentity++) {
                                ?>
                                    <div>
                                        <select class="w-full bg-gray-50 border border-gray-300 rounded-md px-3 py-2 focus:ring-blockchain-primary focus:border-blockchain-primary" 
                                                name="for<?php echo $forentity ?>" id="for<?php echo $forentity ?>">
                                            <option value="">-- Select Entity --</option>
                                            
                                            <?php
                                            foreach ($entities as $entitytxid => $entityname)
                                                echo '<option value="' . html($entitytxid) . '"' . ((@$_POST['for' . $forentity] == $entitytxid) ? ' selected' : '') . '>' . html($entityname) . '</option>';
                                            ?>
                                        </select>
                                    </div>
                                <?php } ?>
                            </div>
                            <p class="text-xs text-gray-500 mt-2">
                                <i class="fas fa-info-circle mr-1"></i>
                                The filter will only be applied to transactions which reference one or more of these entities. 
                                Leave blank to apply this filter to all transactions.
                            </p>
                        </div>
                        
                        <div>
                            <button type="submit" name="testtxfiltercode" class="bg-gray-100 hover:bg-gray-200 text-gray-800 py-2 px-4 rounded-md transition-colors flex items-center">
                                <i class="fas fa-check-circle mr-2"></i>
                                Test Compiling Filter Code Only
                            </button>
                        </div>
                    </div>
                    
                    <!-- Test Send Section -->
                    <div class="mb-8 border-b border-gray-200 pb-8">
                        <h4 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-paper-plane text-blockchain-primary mr-2"></i>
                            Test with Asset Transfer
                        </h4>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="mb-4">
                                <label for="sendfrom" class="block text-sm font-medium text-gray-700 mb-2">From Address:</label>
                                <select class="w-full bg-gray-50 border border-gray-300 rounded-md px-3 py-2 focus:ring-blockchain-primary focus:border-blockchain-primary" 
                                        name="sendfrom" id="sendfrom">
                                    <?php
                                    foreach ($sendaddresses as $address)
                                        echo '<option value="' . html($address) . '"' . ((@$_POST['sendfrom'] == $address) ? ' selected' : '') . '>' . format_address_html($address, true, $labels) . '</option>';
                                    ?>
                                </select>
                            </div>
                            
                            <div class="mb-4">
                                <label for="asset" class="block text-sm font-medium text-gray-700 mb-2">Asset to Send:</label>
                                <select class="w-full bg-gray-50 border border-gray-300 rounded-md px-3 py-2 focus:ring-blockchain-primary focus:border-blockchain-primary" 
                                        name="asset" id="asset">
                                    <?php
                                    foreach ($usableassets as $asset)
                                        echo '<option value="' . html($asset) . '"' . ((@$_POST['asset'] == $asset) ? ' selected' : '') . '>' . html($asset) . '</option>';
                                    ?>
                                </select>
                            </div>
                            
                            <div class="mb-4">
                                <label for="to" class="block text-sm font-medium text-gray-700 mb-2">To Address:</label>
                                <select class="w-full bg-gray-50 border border-gray-300 rounded-md px-3 py-2 focus:ring-blockchain-primary focus:border-blockchain-primary" 
                                        name="to" id="to">
                                    <?php
                                    foreach ($receiveaddresses as $address) {
                                        if ($address == $getinfo['burnaddress'])
                                            continue;
                                        
                                        echo '<option value="' . html($address) . '"' . ((@$_POST['to'] == $address) ? ' selected' : '') . '>' . format_address_html($address, @$keymyaddresses[$address], $labels) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="mb-4">
                                <label for="qty" class="block text-sm font-medium text-gray-700 mb-2">Quantity:</label>
                                <input type="text" class="w-full bg-gray-50 border border-gray-300 rounded-md px-3 py-2 focus:ring-blockchain-primary focus:border-blockchain-primary" 
                                       name="qty" id="qty" placeholder="0.0" value="<?php echo @$_POST['qty'] ?>">
                            </div>
                        </div>
                        
                        <div class="flex items-center mt-4">
                            <button type="submit" name="testtxfiltersend" class="bg-gradient-button hover:opacity-90 text-white py-2 px-4 rounded-md transition-colors flex items-center mr-3">
                                <i class="fas fa-vial mr-2"></i>
                                Test Sending Asset
                            </button>
                            <label class="flex items-center text-sm text-gray-700">
                                <input type="checkbox" name="sendcallbacks" id="sendcallbacks" value="1" 
                                       <?php echo @$_POST['sendcallbacks'] ? 'checked' : '' ?> 
                                       class="h-4 w-4 text-blockchain-primary focus:ring-blockchain-primary border-gray-300 rounded mr-2">
                                Display callback results
                            </label>
                        </div>
                    </div>
                    
                    <!-- Test Raw Transaction Section -->
                    <div class="mb-8 border-b border-gray-200 pb-8">
                        <h4 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-file-code text-blockchain-primary mr-2"></i>
                            Test with Raw Transaction
                        </h4>
                        
                        <div class="mb-4">
                            <label for="rawtx" class="block text-sm font-medium text-gray-700 mb-2">Raw Transaction Hex:</label>
                            <textarea class="w-full h-28 font-mono bg-gray-50 border border-gray-300 rounded-md px-3 py-2 focus:ring-blockchain-primary focus:border-blockchain-primary" 
                                      name="rawtx" id="rawtx"><?php echo html(@$_POST['rawtx']); ?></textarea>
                            <p class="text-xs text-gray-500 mt-1">
                                <i class="fas fa-info-circle mr-1"></i>
                                Raw transactions can be created using the <code class="text-xs bg-gray-100 px-1 py-0.5 rounded">multichain-cli</code> command line tool 
                                and the <code class="text-xs bg-gray-100 px-1 py-0.5 rounded">createrawsendfrom</code> or 
                                <code class="text-xs bg-gray-100 px-1 py-0.5 rounded">createrawtransaction</code> command.
                            </p>
                        </div>
                        
                        <div class="flex items-center">
                            <button type="submit" name="testtxfilterraw" class="bg-gradient-button hover:opacity-90 text-white py-2 px-4 rounded-md transition-colors flex items-center mr-3">
                                <i class="fas fa-vial mr-2"></i>
                                Test Raw Transaction
                            </button>
                            <label class="flex items-center text-sm text-gray-700">
                                <input type="checkbox" name="rawcallbacks" id="rawcallbacks" value="1" 
                                       <?php echo @$_POST['rawcallbacks'] ? 'checked' : '' ?> 
                                       class="h-4 w-4 text-blockchain-primary focus:ring-blockchain-primary border-gray-300 rounded mr-2">
                                Display callback results
                            </label>
                        </div>
                    </div>
                    
                    <!-- Create Filter Section -->
                    <div>
                        <h4 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-plus-circle text-blockchain-primary mr-2"></i>
                            Create On-Chain Transaction Filter
                        </h4>
                        
                        <div class="bg-blue-50 border border-blue-100 rounded-lg p-4 mb-5">
                            <div class="flex items-start">
                                <div class="flex-shrink-0 mt-0.5">
                                    <i class="fas fa-info-circle text-blockchain-primary"></i>
                                </div>
                                <div class="ml-3">
                                    <h5 class="text-sm font-medium text-gray-800">Creating On-Chain Filters</h5>
                                    <p class="text-xs text-gray-600 mt-1">
                                        On-chain filters become part of the blockchain and can be used to validate transactions 
                                        according to custom business logic. Once created, filters require approval before becoming active.
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="mb-4">
                                <label for="createfrom" class="block text-sm font-medium text-gray-700 mb-2">Create From Address:</label>
                                <select class="w-full bg-gray-50 border border-gray-300 rounded-md px-3 py-2 focus:ring-blockchain-primary focus:border-blockchain-primary" 
                                        name="createfrom" id="createfrom">
                                    <?php
                                    foreach ($admincreateaddresses as $address)
                                        echo '<option value="' . html($address) . '"' . ((@$_POST['createfrom'] == $address) ? ' selected' : '') . '>' . format_address_html($address, true, $labels) . '</option>';
                                    ?>
                                </select>
                                <p class="text-xs text-gray-500 mt-1">
                                    <i class="fas fa-key mr-1"></i>
                                    Address must have admin and create permissions
                                </p>
                            </div>
                            
                            <div class="mb-4">
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Filter Name:</label>
                                <input type="text" class="w-full bg-gray-50 border border-gray-300 rounded-md px-3 py-2 focus:ring-blockchain-primary focus:border-blockchain-primary" 
                                       name="name" id="name" placeholder="filter1" value="<?php echo html(@$_POST['name']) ?>">
                                <p class="text-xs text-gray-500 mt-1">
                                    <i class="fas fa-tag mr-1"></i>
                                    Choose a unique, descriptive name for your filter
                                </p>
                            </div>
                        </div>
                        
                        <div class="flex justify-end mt-6">
                            <button type="submit" name="createtxfilter" class="bg-gradient-primary hover:opacity-90 text-white py-2 px-6 rounded-md transition-colors flex items-center shadow-md">
                                <i class="fas fa-plus-circle mr-2"></i>
                                Create Transaction Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for improved functionality -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add line numbers to code textarea
    const codeTextarea = document.getElementById('code');
    if(codeTextarea) {
        codeTextarea.addEventListener('scroll', function() {
            // Could add line number scrolling logic here if needed
        });
    }
    
    // Enable form validation indicators
    const qtyField = document.getElementById('qty');
    if(qtyField) {
        qtyField.addEventListener('input', function(e) {
            const value = e.target.value;
            if(isNaN(value) || value <= 0) {
                qtyField.classList.add('border-red-300');
                qtyField.classList.remove('border-gray-300');
            } else {
                qtyField.classList.remove('border-red-300');
                qtyField.classList.add('border-gray-300');
            }
        });
    }
});
</script>