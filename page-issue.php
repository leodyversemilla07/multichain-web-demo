<?php

define('const_issue_custom_fields', 10);

$max_upload_size = multichain_max_data_size() - 512; // take off space for file name and mime type

$success = false; // set default value

if (@$_POST['issueasset']) {
    $multiple = (int) round(1 / $_POST['units']);

    $addresses = array( // array of addresses to issue units to
        $_POST['to'] => array(
            'issue' => array(
                'raw' => (int) ($_POST['qty'] * $multiple)
            )
        )
    );

    $custom = array();

    for ($index = 0; $index < const_issue_custom_fields; $index++)
        if (strlen(@$_POST['key' . $index]))
            $custom[$_POST['key' . $index]] = $_POST['value' . $index];

    $datas = array( // to create array of data items
        array( // metadata for issuance details
            'create' => 'asset',
            'name' => $_POST['name'],
            'multiple' => $multiple,
            'open' => true,
            'details' => $custom,
        )
    );

    $upload = @$_FILES['upload'];
    $upload_file = @$upload['tmp_name'];

    if (strlen($upload_file)) {
        $upload_size = filesize($upload_file);

        if ($upload_size > $max_upload_size) {
            output_html_error('Uploaded file is too large (' . number_format($upload_size) . ' > ' . number_format($max_upload_size) . ' bytes).');
            return;

        } else {
            $datas[0]['details']['@file'] = fileref_to_string(2, $upload['name'], $upload['type'], $upload_size); // will be in output 2
            $datas[1] = bin2hex(file_to_txout_bin($upload['name'], $upload['type'], file_get_contents($upload_file)));
        }
    }

    if (!count($datas[0]['details'])) // to ensure it's converted to empty JSON object rather than empty JSON array
        $datas[0]['details'] = new stdClass();

    $success = no_displayed_error_result($issuetxid, multichain('createrawsendfrom', $_POST['from'], $addresses, $datas, 'send'));

    if ($success)
        output_success_text('Asset successfully issued in transaction ' . $issuetxid);
}

$getinfo = multichain_getinfo();

$issueaddresses = array();
$keymyaddresses = array();
$receiveaddresses = array();
$labels = array();

if (no_displayed_error_result($getaddresses, multichain('getaddresses', true))) {

    if (
        no_displayed_error_result(
            $listpermissions,
            multichain('listpermissions', 'issue', implode(',', array_get_column($getaddresses, 'address')))
        )
    )
        $issueaddresses = array_get_column($listpermissions, 'address');

    foreach ($getaddresses as $address)
        if ($address['ismine'])
            $keymyaddresses[$address['address']] = true;

    if (no_displayed_error_result($listpermissions, multichain('listpermissions', 'receive')))
        $receiveaddresses = array_get_column($listpermissions, 'address');

    $labels = multichain_labels();
}
?>

<!-- Main grid layout for the page -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-8">
    
    <!-- Left column - Issued Assets List -->
    <div class="md:col-span-1">
        <div class="bg-white rounded-xl shadow-card overflow-hidden border border-gray-100">
            <div class="bg-gradient-primary px-6 py-4">
                <h3 class="text-xl font-mono font-semibold text-white m-0 flex items-center">
                    <i class="fas fa-coins mr-3"></i>Issued Assets
                </h3>
            </div>
            
            <div class="p-4 space-y-6">
                <?php
                if (no_displayed_error_result($listassets, multichain('listassets', '*', true))) {
                    if (count($listassets) > 0) {
                        foreach ($listassets as $asset) {
                            $name = $asset['name'];
                            $issuer = $asset['issues'][0]['issuers'][0];
                            $isHighlighted = ($success && ($name == @$_POST['name']));
                            ?>
                            <!-- Asset Card -->
                            <div class="border <?php echo $isHighlighted ? 'border-green-500 bg-green-50' : 'border-gray-200' ?> rounded-lg overflow-hidden transition-all duration-200 hover:shadow-md">
                                <!-- Asset Header -->
                                <div class="<?php echo $isHighlighted ? 'bg-green-100' : 'bg-gray-50' ?> px-4 py-3 border-b <?php echo $isHighlighted ? 'border-green-200' : 'border-gray-200' ?>">
                                    <div class="flex justify-between items-center">
                                        <h4 class="font-mono font-medium text-lg m-0 text-blockchain-dark flex items-center">
                                            <span><?php echo html($name) ?></span>
                                            <?php if (!$asset['open']) { ?>
                                                <span class="ml-2 px-2 py-0.5 bg-gray-500 text-white text-xs rounded-full">closed</span>
                                            <?php } ?>
                                        </h4>
                                        <?php if ($isHighlighted) { ?>
                                            <span class="bg-green-500 text-white text-xs px-2 py-1 rounded-full">New</span>
                                        <?php } ?>
                                    </div>
                                </div>
                                
                                <!-- Asset Details -->
                                <div class="p-4">
                                    <div class="grid grid-cols-2 gap-2 mb-3">
                                        <div class="bg-gray-50 p-3 rounded border border-gray-100">
                                            <div class="text-xs text-gray-500 mb-1">Quantity</div>
                                            <div class="font-mono font-medium"><?php echo html($asset['issueqty']) ?></div>
                                        </div>
                                        <div class="bg-gray-50 p-3 rounded border border-gray-100">
                                            <div class="text-xs text-gray-500 mb-1">Units</div>
                                            <div class="font-mono font-medium"><?php echo html($asset['units']) ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="text-xs text-gray-500 mb-1">Issuer</div>
                                        <div class="hash-value">
                                            <?php echo format_address_html($issuer, @$keymyaddresses[$issuer], $labels) ?>
                                        </div>
                                    </div>
                                    
                                    <?php
                                    $details = array();
                                    $detailshistory = array();
                                    
                                    foreach ($asset['issues'] as $issue) {
                                        foreach ($issue['details'] as $key => $value) {
                                            $detailshistory[$key][$issue['txid']] = $value;
                                            $details[$key] = $value;
                                        }
                                    }
                                    
                                    if (count(@$detailshistory['@file'])) {
                                    ?>
                                    <div class="mb-3">
                                        <div class="text-xs text-gray-500 mb-1">File</div>
                                        <div class="bg-gray-50 p-2 rounded border border-gray-200">
                                            <?php
                                            $countoutput = 0;
                                            $countprevious = count($detailshistory['@file']) - 1;
                                            
                                            foreach ($detailshistory['@file'] as $txid => $string) {
                                                $fileref = string_to_fileref($string);
                                                if (is_array($fileref)) {
                                                    $file_name = $fileref['filename'];
                                                    $file_size = $fileref['filesize'];
                                                    
                                                    if ($countoutput == 1) // first previous version
                                                        echo '<div class="text-xs text-gray-500 mt-2">(' . $countprevious . ' previous ' . (($countprevious > 1) ? 'files' : 'file') . ': ';
                                                    
                                                    echo '<a href="./download-file.php?chain=' . html($_GET['chain']) . '&txid=' . html($txid) . '&vout=' . html($fileref['vout']) . '" class="text-blockchain-primary hover:underline">' .
                                                        (strlen($file_name) ? html($file_name) : 'Download') . '</a>' .
                                                        (($file_size && !$countoutput) ? ' <span class="text-gray-500">(' . number_format(ceil($file_size / 1024)) . ' KB)</span>' : '');
                                                    
                                                    $countoutput++;
                                                }
                                            }
                                            
                                            if ($countoutput > 1)
                                                echo ')</div>';
                                            ?>
                                        </div>
                                    </div>
                                    <?php
                                    }
                                    
                                    foreach ($details as $key => $value) {
                                        if ($key == '@file')
                                            continue;
                                    ?>
                                    <div class="mb-3">
                                        <div class="text-xs text-gray-500 mb-1"><?php echo html($key) ?></div>
                                        <div class="bg-gray-50 p-2 rounded border border-gray-200">
                                            <?php echo html($value) ?>
                                            <?php if (count($detailshistory[$key]) > 1) { ?>
                                                <div class="text-xs text-gray-500 mt-2">
                                                    (previous values: <?php echo html(implode(', ', array_slice(array_reverse($detailshistory[$key]), 1))) ?>)
                                                </div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                    <?php
                                    }
                                    ?>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        ?>
                        <div class="flex flex-col items-center justify-center p-8 text-gray-400 bg-gray-50 rounded-lg border border-gray-100">
                            <i class="fas fa-coins text-4xl mb-3 opacity-30"></i>
                            <p class="text-center">No assets have been issued yet</p>
                            <p class="text-center text-sm">Use the form to issue your first asset</p>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
        </div>
    </div>
    
    <!-- Right column - Issue Asset Form -->
    <div class="md:col-span-2">
        <div class="bg-white rounded-xl shadow-card overflow-hidden border border-gray-100">
            <div class="bg-gradient-primary px-6 py-4">
                <h3 class="text-xl font-mono font-semibold text-white m-0 flex items-center">
                    <i class="fas fa-plus-circle mr-3"></i>Issue Asset
                </h3>
            </div>
            
            <div class="p-6">
                <form method="post" enctype="multipart/form-data" action="./?chain=<?php echo html($_GET['chain']) ?>&page=<?php echo html($_GET['page']) ?>">
                    <!-- From Address -->
                    <div class="mb-6">
                        <label for="from" class="block text-sm font-medium text-gray-700 mb-2">From Address</label>
                        <select name="from" id="from" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blockchain-primary focus:border-blockchain-primary">
                            <?php foreach ($issueaddresses as $address) { ?>
                                <option value="<?php echo html($address) ?>">
                                    <?php echo format_address_html($address, true, $labels) ?>
                                </option>
                            <?php } ?>
                        </select>
                        <?php if (count($issueaddresses) == 0) { ?>
                            <p class="mt-2 text-sm text-red-600">No addresses with issue permissions found.</p>
                        <?php } ?>
                    </div>
                    
                    <!-- Asset Details -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Asset Name</label>
                            <input type="text" name="name" id="name" placeholder="asset1" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blockchain-primary focus:border-blockchain-primary">
                        </div>
                        <div>
                            <label for="qty" class="block text-sm font-medium text-gray-700 mb-2">Quantity</label>
                            <input type="text" name="qty" id="qty" placeholder="1000.0" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blockchain-primary focus:border-blockchain-primary">
                        </div>
                        <div>
                            <label for="units" class="block text-sm font-medium text-gray-700 mb-2">Units</label>
                            <input type="text" name="units" id="units" placeholder="0.01" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blockchain-primary focus:border-blockchain-primary">
                        </div>
                        <div>
                            <label for="to" class="block text-sm font-medium text-gray-700 mb-2">To Address</label>
                            <select name="to" id="to" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blockchain-primary focus:border-blockchain-primary">
                                <?php 
                                foreach ($receiveaddresses as $address) {
                                    if ($address == $getinfo['burnaddress'])
                                        continue;
                                    ?>
                                    <option value="<?php echo html($address) ?>">
                                        <?php echo format_address_html($address, @$keymyaddresses[$address], $labels) ?>
                                    </option>
                                    <?php
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Note about open assets -->
                    <div class="mb-6 bg-blue-50 border-l-4 border-blue-400 p-4 rounded">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-info-circle text-blue-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    In this demo, the asset will be open, allowing further issues in future.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- File Upload -->
                    <div class="mb-6">
                        <label for="upload" class="block text-sm font-medium text-gray-700 mb-2">
                            Upload File
                            <span class="text-xs font-normal text-gray-500 ml-1">
                                (Max <?php echo floor($max_upload_size / 1024) ?> KB)
                            </span>
                        </label>
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-blockchain-primary transition-colors">
                            <div class="space-y-1 text-center">
                                <i class="fas fa-file-upload text-gray-400 text-3xl mb-3"></i>
                                <div class="flex text-sm text-gray-600">
                                    <label for="upload" class="relative cursor-pointer bg-white rounded-md font-medium text-blockchain-primary hover:text-blockchain-dark focus-within:outline-none">
                                        <span>Upload a file</span>
                                        <input id="upload" name="upload" type="file" class="sr-only">
                                    </label>
                                    <p class="pl-1">or drag and drop</p>
                                </div>
                                <p class="text-xs text-gray-500">Any file up to <?php echo floor($max_upload_size / 1024) ?> KB</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Custom Fields -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-4">Custom Fields</label>
                        
                        <div class="space-y-3">
                            <?php for ($index = 0; $index < const_issue_custom_fields; $index++) { ?>
                                <div class="grid grid-cols-3 gap-3">
                                    <div class="col-span-1">
                                        <input type="text" name="key<?php echo $index ?>" id="key<?php echo $index ?>" placeholder="key" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blockchain-primary focus:border-blockchain-primary">
                                    </div>
                                    <div class="col-span-2">
                                        <input type="text" name="value<?php echo $index ?>" id="value<?php echo $index ?>" placeholder="value" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blockchain-primary focus:border-blockchain-primary">
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                        
                        <p class="mt-2 text-sm text-gray-500">Add metadata to your asset by defining key-value pairs</p>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="flex justify-end">
                        <button type="submit" name="issueasset" value="Issue Asset" class="px-6 py-3 bg-gradient-button text-white font-medium rounded-lg hover:shadow-lg transition-all">
                            <i class="fas fa-coins mr-2"></i>Issue Asset
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Add drag and drop functionality for file upload
    const dropArea = document.querySelector('label[for="upload"]').parentNode.parentNode;
    const fileInput = document.getElementById('upload');
    
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, preventDefaults, false);
    });
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    ['dragenter', 'dragover'].forEach(eventName => {
        dropArea.addEventListener(eventName, highlight, false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, unhighlight, false);
    });
    
    function highlight() {
        dropArea.classList.add('border-blockchain-primary', 'bg-blue-50');
    }
    
    function unhighlight() {
        dropArea.classList.remove('border-blockchain-primary', 'bg-blue-50');
    }
    
    dropArea.addEventListener('drop', handleDrop, false);
    
    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        fileInput.files = files;
    }
    
    // Show filename when file is selected
    fileInput.addEventListener('change', function() {
        const fileNameDisplay = document.createElement('p');
        fileNameDisplay.classList.add('mt-2', 'text-sm', 'text-blockchain-primary', 'font-mono');
        
        if (this.files && this.files[0]) {
            // Remove any previous filename display
            const prevDisplay = dropArea.querySelector('.text-blockchain-primary');
            if (prevDisplay) prevDisplay.remove();
            
            fileNameDisplay.innerHTML = '<i class="fas fa-check-circle mr-1"></i> ' + this.files[0].name;
            dropArea.appendChild(fileNameDisplay);
        }
    });
</script>