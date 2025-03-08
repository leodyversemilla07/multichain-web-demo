<?php

	define('const_update_custom_fields', 5);
	
	$max_upload_size=multichain_max_data_size()-512; // take off space for file name and mime type

	$success=false; // set default value

	if (@$_POST['updateasset']) {
		if (!no_displayed_error_result($listassets, multichain('listassets', $_POST['issuetxid'], true)))
			return;
			
		$from=$listassets[0]['issues'][0]['issuers'][0];
		$multiple=$listassets[0]['multiple'];

		$addresses=array( // array of addresses to issue units to
			$_POST['to'] => array(
				'issuemore' => array(
					'asset' => $_POST['issuetxid'],
					'raw' => (int)($_POST['qty']*$multiple),
				)
			)
		);
		
		$custom=array();
		
		for ($index=0; $index<const_update_custom_fields; $index++)
			if (strlen(@$_POST['key'.$index]))
				$custom[$_POST['key'.$index]]=$_POST['value'.$index];

		$datas=array( // to create array of data items
			array( // metadata for reissuance details
				'update' => $_POST['issuetxid'],
				'details' => $custom,
			)
		);
		
		$upload=@$_FILES['upload'];
		$upload_file=@$upload['tmp_name'];

		if (strlen($upload_file)) {
			$upload_size=filesize($upload_file);

			if ($upload_size>$max_upload_size) {
				output_html_error('Uploaded file is too large ('.number_format($upload_size).' > '.number_format($max_upload_size).' bytes).');
				return;

			} else {
				$datas[0]['details']['@file']=fileref_to_string(2, $upload['name'], $upload['type'], $upload_size); // will be in output 2
				$datas[1]=bin2hex(file_to_txout_bin($upload['name'], $upload['type'], file_get_contents($upload_file)));
			}
		}
		
		if (!count($datas[0]['details'])) // to ensure it's converted to empty JSON object rather than empty JSON array
			$datas[0]['details']=new stdClass();
		
		$success=no_displayed_error_result($issuetxid, multichain('createrawsendfrom', $from, $addresses, $datas, 'send'));
			
		if ($success)
			output_success_text('Asset successfully updated in transaction '.$issuetxid);
	}

	$getinfo=multichain_getinfo();

	$issueaddresses=array();
	$keymyaddresses=array();
	$receiveaddresses=array();
	$labels=array();

	if (no_displayed_error_result($getaddresses, multichain('getaddresses', true))) {

		if (no_displayed_error_result($listpermissions,
			multichain('listpermissions', 'send', implode(',', array_get_column($getaddresses, 'address')))
		))
			$followaddresses=array_get_column($listpermissions, 'address');
		
		foreach ($getaddresses as $address)
			if ($address['ismine'])
				$keymyaddresses[$address['address']]=true;
				
		if (no_displayed_error_result($listpermissions, multichain('listpermissions', 'receive')))
			$receiveaddresses=array_get_column($listpermissions, 'address');

		$labels=multichain_labels();
	}
?>

<div class="grid grid-cols-1 md:grid-cols-12 gap-6">
    <!-- Left Column - Asset Listings -->
    <div class="md:col-span-5">
        <div class="bg-white rounded-lg shadow-card overflow-hidden">
            <div class="bg-gradient-primary p-4 text-white">
                <h3 class="text-xl font-mono font-medium m-0 flex items-center">
                    <i class="fas fa-coins mr-2"></i> My Open Issued Assets
                </h3>
            </div>
            <div class="p-4 space-y-5">
<?php
    if (no_displayed_error_result($listassets, multichain('listassets', '*', true))) {
        $hasAssets = false;
        
        foreach ($listassets as $asset) {
            $name=$asset['name'];
            $issuer=$asset['issues'][0]['issuers'][0];
            
            if (!($asset['open'] && @$keymyaddresses[$issuer]))
                continue;
                
            $hasAssets = true;
?>
                <div class="bg-white rounded-lg border <?php echo ($success && ($name==@$_POST['name'])) ? 'border-green-500 bg-green-50' : 'border-gray-200' ?> overflow-hidden">
                    <div class="bg-gray-50 py-2 px-4 border-b border-gray-200">
                        <h4 class="font-mono text-lg font-medium text-gray-800 m-0 flex items-center">
                            <?php echo html($name)?>
                            <?php if($asset['open']): ?>
                                <span class="ml-2 text-xs bg-green-500 text-white px-2 py-1 rounded-full">Open</span>
                            <?php else: ?>
                                <span class="ml-2 text-xs bg-gray-500 text-white px-2 py-1 rounded-full">Closed</span>
                            <?php endif; ?>
                        </h4>
                    </div>
                    
                    <div class="divide-y divide-gray-200">
                        <div class="flex py-2 px-4">
                            <div class="w-1/3 font-medium text-gray-600">Quantity</div>
                            <div class="w-2/3 font-mono"><?php echo html($asset['issueqty'])?></div>
                        </div>
                        
                        <div class="flex py-2 px-4">
                            <div class="w-1/3 font-medium text-gray-600">Units</div>
                            <div class="w-2/3 font-mono"><?php echo html($asset['units'])?></div>
                        </div>
                        
                        <div class="flex py-2 px-4">
                            <div class="w-1/3 font-medium text-gray-600">Issuer</div>
                            <div class="w-2/3">
                                <span class="hash-value"><?php echo format_address_html($issuer, @$keymyaddresses[$issuer], $labels)?></span>
                            </div>
                        </div>
<?php
            $details=array();
            $detailshistory=array();
            
            foreach ($asset['issues'] as $issue)
                foreach ($issue['details'] as $key => $value) {
                    $detailshistory[$key][$issue['txid']]=$value;
                    $details[$key]=$value;
                }

            if (@count(@$detailshistory['@file'])) {
?>
                        <div class="flex py-2 px-4">
                            <div class="w-1/3 font-medium text-gray-600">File</div>
                            <div class="w-2/3">
<?php
                $countoutput=0;
                $countprevious=count($detailshistory['@file'])-1;

                foreach ($detailshistory['@file'] as $txid => $string) {
                    $fileref=string_to_fileref($string);
                    if (is_array($fileref)) {
                    
                        $file_name=$fileref['filename'];
                        $file_size=$fileref['filesize'];
                        
                        if ($countoutput==1) // first previous version
                            echo '<div class="mt-2 text-sm text-gray-500">('.$countprevious.' previous '.(($countprevious>1) ? 'files' : 'file').': ';
                        
                        echo '<a href="./download-file.php?chain='.html($_GET['chain']).'&txid='.html($txid).'&vout='.html($fileref['vout']).'" class="text-blockchain-primary hover:underline">'.
                            (strlen($file_name) ? html($file_name) : 'Download').
                            '</a>'.
                            ( ($file_size && !$countoutput) ? html(' ('.number_format(ceil($file_size/1024)).' KB)') : '');
                        
                        $countoutput++;
                    }
                }
                
                if ($countoutput>1)
                    echo ')</div>';
?>
                            </div>
                        </div>
<?php
            }

            foreach ($details as $key => $value) {
                if ($key=='@file')
                    continue;
?>
                        <div class="flex py-2 px-4">
                            <div class="w-1/3 font-medium text-gray-600"><?php echo html($key)?></div>
                            <div class="w-2/3">
                                <?php echo html($value)?>
                                <?php if(count($detailshistory[$key])>1): ?>
                                    <div class="mt-1 text-sm text-gray-500">
                                        Previous values: <?php echo html(implode(', ', array_slice(array_reverse($detailshistory[$key]), 1))) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
<?php
            }
?>
                    </div>
                </div>
<?php
        }
        
        if (!$hasAssets) {
?>
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 text-center">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-200 text-gray-500 mb-3">
                        <i class="fas fa-info"></i>
                    </div>
                    <h4 class="text-gray-700 font-medium mb-2">No Open Assets Found</h4>
                    <p class="text-gray-500 text-sm">You don't have any open issued assets to update. Issue new assets first.</p>
                </div>
<?php
        }
    }
?>
            </div>
        </div>
    </div>
    
    <!-- Right Column - Update Form -->
    <div class="md:col-span-7">
        <div class="bg-white rounded-lg shadow-card overflow-hidden">
            <div class="bg-gradient-primary p-4 text-white">
                <h3 class="text-xl font-mono font-medium m-0 flex items-center">
                    <i class="fas fa-edit mr-2"></i> Update Asset
                </h3>
            </div>
            
            <div class="p-6">
                <form method="post" enctype="multipart/form-data" action="./?chain=<?php echo html($_GET['chain'])?>&page=<?php echo html($_GET['page'])?>">
                    <!-- Asset Selection -->
                    <div class="mb-6">
                        <label for="issuetxid" class="block text-sm font-medium text-gray-700 mb-1">Asset to Update</label>
                        <div class="relative">
                            <select name="issuetxid" id="issuetxid" class="form-select block w-full pl-10 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blockchain-primary focus:border-blockchain-primary rounded-md shadow-sm">
<?php
    foreach ($listassets as $asset) {
        $issuer=$asset['issues'][0]['issuers'][0];

        if (($asset['open'] && @$keymyaddresses[$issuer])) {
?>
                                <option value="<?php echo html($asset['issuetxid'])?>"><?php echo html($asset['name'])?></option>
<?php
        }
    }
?>
                            </select>
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-coins text-gray-400"></i>
                            </div>
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <i class="fas fa-chevron-down text-gray-400"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Asset Quantity -->
                    <div class="mb-6">
                        <label for="qty" class="block text-sm font-medium text-gray-700 mb-1">Quantity to Issue</label>
                        <div class="relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-plus-circle text-gray-400"></i>
                            </div>
                            <input type="number" name="qty" id="qty" value="0" step="any" class="form-input block w-full pl-10 pr-12 sm:text-sm border-gray-300 rounded-md focus:ring-blockchain-primary focus:border-blockchain-primary">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">Units</span>
                            </div>
                        </div>
                    </div>

                    <!-- Recipient Address -->
                    <div class="mb-6">
                        <label for="to" class="block text-sm font-medium text-gray-700 mb-1">Recipient Address</label>
                        <div class="relative">
                            <select name="to" id="to" class="form-select block w-full pl-10 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blockchain-primary focus:border-blockchain-primary rounded-md shadow-sm">
<?php
    foreach ($receiveaddresses as $address) {
        if ($address==$getinfo['burnaddress'])
            continue;
?>
                                <option value="<?php echo html($address)?>"><?php echo format_address_html($address, @$keymyaddresses[$address], $labels)?></option>
<?php
    }
?>
                            </select>
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <i class="fas fa-chevron-down text-gray-400"></i>
                            </div>
                        </div>
                    </div>

                    <!-- File Upload -->
                    <div class="mb-6">
                        <label for="upload" class="block text-sm font-medium text-gray-700 mb-1">Update File</label>
                        <div class="flex items-center">
                            <div class="relative flex-grow">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-file-upload text-gray-400"></i>
                                </div>
                                <input type="file" name="upload" id="upload" class="form-input block w-full pl-10 py-2 text-sm border-gray-300 rounded-md focus:ring-blockchain-primary focus:border-blockchain-primary">
                            </div>
                            <div class="ml-2 text-xs text-gray-500 whitespace-nowrap">
                                Max <?php echo floor($max_upload_size/1024)?> KB
                            </div>
                        </div>
                    </div>

                    <!-- Custom Field Section -->
                    <div class="mb-6">
                        <div class="flex items-center mb-3">
                            <h4 class="text-base font-medium text-gray-700 m-0">Update Metadata Fields</h4>
                            <div class="flex-grow ml-3 h-px bg-gray-200"></div>
                        </div>

                        <div class="space-y-3">
<?php
    for ($index=0; $index<const_update_custom_fields; $index++) {
?>
                            <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-2">
                                <div class="sm:w-1/3">
                                    <div class="relative rounded-md shadow-sm">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-key text-gray-400"></i>
                                        </div>
                                        <input type="text" name="key<?php echo $index?>" id="key<?php echo $index?>" placeholder="Key name" class="focus:ring-blockchain-primary focus:border-blockchain-primary block w-full pl-10 sm:text-sm border-gray-300 rounded-md">
                                    </div>
                                </div>
                                <div class="sm:w-2/3">
                                    <div class="relative rounded-md shadow-sm">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-edit text-gray-400"></i>
                                        </div>
                                        <input type="text" name="value<?php echo $index?>" id="value<?php echo $index?>" placeholder="Value" class="focus:ring-blockchain-primary focus:border-blockchain-primary block w-full pl-10 sm:text-sm border-gray-300 rounded-md">
                                    </div>
                                </div>
                            </div>
<?php
    }
?>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-3">
                        <button type="submit" name="updateasset" value="1" class="w-full sm:w-auto inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-gradient-button hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blockchain-primary transition duration-150 ease-in-out">
                            <i class="fas fa-sync-alt mr-2"></i>
                            Update Asset
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
