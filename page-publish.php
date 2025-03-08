<?php

$max_upload_size = multichain_max_data_size() - 512; // take off space for file name and mime type
$allow_multi_keys = multichain_has_multi_item_keys();

if (@$_POST['publish']) {

	$upload = @$_FILES['upload'];
	$upload_file = @$upload['tmp_name'];

	if (strlen($upload_file)) {
		$upload_size = filesize($upload_file);

		if ($upload_size > $max_upload_size) {
			output_html_error('Uploaded file is too large (' . number_format($upload_size) . ' > ' . number_format($max_upload_size) . ' bytes).');
			return;

		} else
			$data = bin2hex(file_to_txout_bin($upload['name'], $upload['type'], file_get_contents($upload_file)));

	} elseif (multichain_has_json_text_items()) { // use native JSON and text objects in MultiChain 2.0
		if (strlen($_POST['json'])) {
			$json = json_decode($_POST['json']);

			if ($json === null) {
				output_html_error('The entered JSON structure does not appear to be valid');
				return;
			} else
				$data = array('json' => $json);

		} else
			$data = array('text' => $_POST['text']);

	} else // convert entered text to binary for MultiChain 1.0
		$data = bin2hex(string_to_txout_bin($_POST['text']));

	$keys = preg_split('/\n|\r\n?/', trim($_POST['key']));
	if (count($keys) <= 1) // convert to single key parameter if only one key
		$keys = $keys[0];

	if ($_POST['offchain']) // need to separate cases here since MultiChain 1.0 publishfrom API has no 'options' parameter
		$result = multichain('publishfrom', $_POST['from'], $_POST['name'], $keys, $data, 'offchain');
	else
		$result = multichain('publishfrom', $_POST['from'], $_POST['name'], $keys, $data);

	if (no_displayed_error_result($publishtxid, $result))
		output_success_text('Item successfully published in transaction ' . $publishtxid);
}

$labels = multichain_labels();

no_displayed_error_result($liststreams, multichain('liststreams', '*', true));

if (no_displayed_error_result($getaddresses, multichain('getaddresses', true))) {
	foreach ($getaddresses as $index => $address)
		if (!$address['ismine'])
			unset($getaddresses[$index]);

	if (
		no_displayed_error_result(
			$listpermissions,
			multichain('listpermissions', 'send', implode(',', array_get_column($getaddresses, 'address')))
		)
	)
		$sendaddresses = array_get_column($listpermissions, 'address');
}

?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-8">
    <!-- Left Column: Stream Data Publisher -->
    <div class="md:col-span-2">
        <div class="bg-white rounded-xl shadow-card overflow-hidden mb-8">
            <!-- Header section -->
            <div class="bg-gradient-primary px-6 py-4 relative">
                <div class="flex items-center relative z-10">
                    <div class="mr-4 p-3 rounded-full bg-white/20 border border-white/30 shadow-lg">
                        <i class="fas fa-upload text-white text-xl"></i>
                    </div>
                    <h3 class="text-white text-xl font-semibold m-0">Publish to Stream</h3>
                </div>
                <!-- Decorative pattern overlay -->
                <div class="absolute inset-0 opacity-10" 
                    style="background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCA2MCA2MCIgd2lkdGg9IjYwIiBoZWlnaHQ9IjYwIj48cGF0aCBkPSJNMzAgNUw1IDE3LjVWNDIuNUwzMCA1NUw1NSA0Mi41VjE3LjVMMzAgNVpNMzAgMjBMMTUgMjcuNVYxMi41TDMwIDIwWk0zNSAxMi41VjI3LjVMMjAgMzVMMzUgNDIuNVYyNy41TDUwIDIwTDM1IDEyLjVaIiBmaWxsPSIjZmZmIi8+PC9zdmc+');">
                </div>
            </div>

            <!-- Form Content -->
            <div class="px-6 py-6">
                <div class="mb-5 bg-blue-50 rounded-lg p-4 border border-blue-100">
                    <div class="flex">
                        <div class="flex-shrink-0 text-blue-500">
                            <i class="fas fa-info-circle text-xl"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">About Publishing to Streams</h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <p>You can publish different types of data to a blockchain stream:</p>
                                <ul class="list-disc mt-2 pl-5 space-y-1">
                                    <li>Files (binary data) - documents, images, or any other file type</li>
                                    <li>JSON data - structured data objects</li>
                                    <li>Plain text - simple text messages</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <form method="post" action="./?chain=<?php echo html($_GET['chain']) ?>&page=<?php echo html($_GET['page']) ?>" 
                    enctype="multipart/form-data" class="space-y-6">
                    
                    <!-- From Address Field -->
                    <div class="mb-6">
                        <label for="from" class="block text-sm font-medium text-gray-700 mb-1">
                            From address <span class="text-xs text-gray-500">(with send permissions)</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user-circle text-gray-400"></i>
                            </div>
                            <select class="blockchain-data block w-full pl-10 pr-10 py-3 text-gray-700 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blockchain-primary focus:border-blockchain-primary appearance-none" name="from" id="from">
                                <?php foreach ($sendaddresses as $address) { ?>
                                    <option value="<?php echo html($address) ?>">
                                        <?php echo format_address_html($address, true, $labels) ?>
                                    </option>
                                <?php } ?>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
                                <i class="fas fa-chevron-down text-gray-400"></i>
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Select the address that will publish the data to the stream</p>
                    </div>
                    
                    <!-- Stream Selection Field -->
                    <div class="mb-6">
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                            Target stream
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-stream text-gray-400"></i>
                            </div>
                            <select class="blockchain-data block w-full pl-10 pr-10 py-3 text-gray-700 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blockchain-primary focus:border-blockchain-primary appearance-none" name="name" id="name">
                                <?php foreach ($liststreams as $stream) {
                                    if ($stream['name'] != 'root') { ?>
                                        <option value="<?php echo html($stream['name']) ?>"><?php echo html($stream['name']) ?></option>
                                    <?php }
                                } ?>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
                                <i class="fas fa-chevron-down text-gray-400"></i>
                            </div>
                        </div>
                        
                        <?php if (multichain_has_off_chain_items()) { ?>
                            <div class="mt-2 flex items-center">
                                <input type="checkbox" id="offchain" name="offchain" value="1" 
                                    class="h-4 w-4 text-blockchain-primary focus:ring-blockchain-primary border-gray-300 rounded">
                                <label for="offchain" class="ml-2 block text-sm text-gray-600">
                                    Publish as off-chain item
                                    <span class="text-xs text-gray-500 ml-1">(data stored outside the blockchain)</span>
                                </label>
                            </div>
                        <?php } ?>
                    </div>
                    
                    <!-- Keys Field -->
                    <div class="mb-6">
                        <label for="key" class="block text-sm font-medium text-gray-700 mb-1">
                            <?php echo $allow_multi_keys ? 'Stream keys' : 'Stream key' ?>
                            <span class="text-xs text-gray-500">(optional)</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-key text-gray-400"></i>
                            </div>
                            <?php if ($allow_multi_keys) { ?>
                                <textarea 
                                    class="blockchain-data pl-10 w-full py-3 text-gray-700 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blockchain-primary focus:border-blockchain-primary" 
                                    rows="3" 
                                    name="key" 
                                    id="key"
                                    placeholder="Enter one key per line"></textarea>
                                <p class="mt-1 text-xs text-gray-500">Keys help categorize and search for items in the stream. Enter each key on a new line.</p>
                            <?php } else { ?>
                                <input class="blockchain-data block w-full pl-10 py-3 text-gray-700 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blockchain-primary focus:border-blockchain-primary" 
                                    type="text" 
                                    name="key" 
                                    id="key" 
                                    placeholder="Enter a key to help identify this item">
                                <p class="mt-1 text-xs text-gray-500">A key helps categorize and search for items in the stream</p>
                            <?php } ?>
                        </div>
                    </div>
                    
                    <!-- Data Content Tabs -->
                    <div x-data="{ activeTab: 'file' }" class="border border-gray-200 rounded-lg overflow-hidden">
                        <!-- Tab Navigation -->
                        <div class="flex border-b border-gray-200 bg-gray-50">
                            <button type="button" @click="activeTab = 'file'" 
                                :class="{'bg-white border-blockchain-primary text-blockchain-primary': activeTab === 'file', 'text-gray-500': activeTab !== 'file'}"
                                class="flex-1 py-3 px-4 text-sm font-medium text-center border-b-2 focus:outline-none">
                                <i class="fas fa-file-upload mr-2"></i> Upload File
                            </button>
                            
                            <?php if (multichain_has_json_text_items()) { ?>
                                <button type="button" @click="activeTab = 'json'" 
                                    :class="{'bg-white border-blockchain-primary text-blockchain-primary': activeTab === 'json', 'text-gray-500': activeTab !== 'json'}"
                                    class="flex-1 py-3 px-4 text-sm font-medium text-center border-b-2 focus:outline-none">
                                    <i class="fas fa-code mr-2"></i> JSON Data
                                </button>
                            <?php } ?>
                            
                            <button type="button" @click="activeTab = 'text'" 
                                :class="{'bg-white border-blockchain-primary text-blockchain-primary': activeTab === 'text', 'text-gray-500': activeTab !== 'text'}"
                                class="flex-1 py-3 px-4 text-sm font-medium text-center border-b-2 focus:outline-none">
                                <i class="fas fa-font mr-2"></i> Text
                            </button>
                        </div>
                        
                        <!-- Tab Content -->
                        <div class="p-5">
                            <!-- File Upload Tab -->
                            <div x-show="activeTab === 'file'" x-cloak>
                                <div class="space-y-4">
                                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center" 
                                        x-data="{ fileName: '', fileSelected: false }"
                                        @dragover.prevent="$el.classList.add('border-blockchain-primary')" 
                                        @dragleave.prevent="$el.classList.remove('border-blockchain-primary')"
                                        @drop.prevent="$el.classList.remove('border-blockchain-primary')">
                                        
                                        <input type="file" name="upload" id="upload" class="hidden" 
                                            @change="fileName = $event.target.files[0] ? $event.target.files[0].name : ''; fileSelected = !!$event.target.files[0]">
                                        
                                        <label for="upload" class="cursor-pointer">
                                            <template x-if="!fileSelected">
                                                <div>
                                                    <i class="fas fa-cloud-upload-alt text-gray-400 text-4xl mb-2"></i>
                                                    <p class="text-gray-700 font-medium">Drop file here or click to upload</p>
                                                    <p class="text-gray-500 text-sm mt-1">Maximum size: <?php echo floor($max_upload_size / 1024) ?> KB</p>
                                                </div>
                                            </template>
                                            <template x-if="fileSelected">
                                                <div>
                                                    <i class="fas fa-file-alt text-blockchain-primary text-4xl mb-2"></i>
                                                    <p class="text-gray-700 font-medium" x-text="fileName"></p>
                                                    <p class="text-gray-500 text-sm mt-1">Click to change file</p>
                                                </div>
                                            </template>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- JSON Tab -->
                            <?php if (multichain_has_json_text_items()) { ?>
                                <div x-show="activeTab === 'json'" x-cloak>
                                    <textarea 
                                        class="w-full h-64 p-3 font-mono text-sm bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blockchain-primary focus:border-blockchain-primary" 
                                        name="json" 
                                        id="json"
                                        placeholder='{
  "property": "value",
  "numbers": [1, 2, 3],
  "object": {
    "nested": "data"
  }
}'></textarea>
                                    <p class="mt-2 text-xs text-gray-500">Enter valid JSON data. The structure will be stored directly in the blockchain stream.</p>
                                </div>
                            <?php } ?>
                            
                            <!-- Text Tab -->
                            <div x-show="activeTab === 'text'" x-cloak>
                                <textarea 
                                    class="w-full h-64 p-3 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blockchain-primary focus:border-blockchain-primary" 
                                    name="text" 
                                    id="text"
                                    placeholder="Enter plain text content to store in the blockchain stream..."></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="mt-8">
                        <button type="submit" name="publish" class="w-full flex justify-center items-center px-6 py-3 bg-gradient-button text-white font-medium rounded-lg hover:shadow-lg transition-all">
                            <i class="fas fa-paper-plane mr-2"></i>
                            Publish to Stream
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Right Column: Help and Guidelines -->
    <div class="md:col-span-1">
        <!-- Stream Data Types Card -->
        <div class="bg-white rounded-xl shadow-card overflow-hidden mb-6">
            <div class="bg-blockchain-dark px-5 py-4 relative">
                <h3 class="text-white text-lg font-semibold m-0 flex items-center">
                    <i class="fas fa-question-circle mr-2"></i>
                    Stream Data Guide
                </h3>
                <!-- Bottom border accent with gradient -->
                <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-blockchain-primary to-blockchain-secondary"></div>
            </div>
            
            <div class="p-5 space-y-4">
                <div class="flex items-start">
                    <div class="mr-3 p-2 rounded-full bg-blue-50 text-blockchain-primary">
                        <i class="fas fa-file-upload"></i>
                    </div>
                    <div>
                        <h5 class="text-sm font-medium text-gray-800">File Upload</h5>
                        <p class="text-xs text-gray-600 mt-1">
                            Upload documents, images, or any file type. Max size: <?php echo floor($max_upload_size / 1024) ?> KB.
                        </p>
                    </div>
                </div>
                
                <?php if (multichain_has_json_text_items()) { ?>
                    <div class="flex items-start">
                        <div class="mr-3 p-2 rounded-full bg-blue-50 text-blockchain-primary">
                            <i class="fas fa-code"></i>
                        </div>
                        <div>
                            <h5 class="text-sm font-medium text-gray-800">JSON Data</h5>
                            <p class="text-xs text-gray-600 mt-1">
                                Store structured data in JSON format. Must be valid JSON object or array.
                            </p>
                        </div>
                    </div>
                <?php } ?>
                
                <div class="flex items-start">
                    <div class="mr-3 p-2 rounded-full bg-blue-50 text-blockchain-primary">
                        <i class="fas fa-font"></i>
                    </div>
                    <div>
                        <h5 class="text-sm font-medium text-gray-800">Text Content</h5>
                        <p class="text-xs text-gray-600 mt-1">
                            Store plain text data such as notes, messages, or simple content.
                        </p>
                    </div>
                </div>
                
                <div class="border-t border-gray-100 pt-4">
                    <h5 class="text-sm font-medium text-gray-800">About Stream Keys</h5>
                    <p class="text-xs text-gray-600 mt-1">
                        Keys help categorize and find data. Think of them like tags or identifiers for your stream items.
                        <?php if ($allow_multi_keys) { ?>
                            You can use multiple keys by entering each on a new line.
                        <?php } ?>
                    </p>
                </div>
                
                <?php if (multichain_has_off_chain_items()) { ?>
                    <div class="border-t border-gray-100 pt-4">
                        <h5 class="text-sm font-medium text-gray-800">Off-chain Storage</h5>
                        <p class="text-xs text-gray-600 mt-1">
                            Off-chain items store data outside the blockchain with only a reference hash in the chain.
                            This is more efficient for larger data but requires nodes to maintain the off-chain data.
                        </p>
                    </div>
                <?php } ?>
            </div>
        </div>
        
        <!-- Stream Management Card -->
        <div class="bg-white rounded-xl shadow-card overflow-hidden">
            <div class="bg-blockchain-dark px-5 py-4 relative">
                <h3 class="text-white text-lg font-semibold m-0 flex items-center">
                    <i class="fas fa-cog mr-2"></i>
                    Stream Management
                </h3>
                <!-- Bottom border accent with gradient -->
                <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-blockchain-primary to-blockchain-secondary"></div>
            </div>
            
            <div class="divide-y divide-gray-100">
                <a href="./?chain=<?php echo html($_GET['chain']) ?>&page=view" class="block p-4 hover:bg-gray-50 transition-all">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="mr-3 p-2 rounded-full bg-gray-50 text-blockchain-primary">
                                <i class="fas fa-search"></i>
                            </div>
                            <div>
                                <h5 class="text-sm font-medium text-gray-800">View Stream Items</h5>
                                <p class="text-xs text-gray-600 mt-1">
                                    Browse all stream contents
                                </p>
                            </div>
                        </div>
                        <i class="fas fa-chevron-right text-gray-300"></i>
                    </div>
                </a>
                
                <a href="./?chain=<?php echo html($_GET['chain']) ?>&page=create" class="block p-4 hover:bg-gray-50 transition-all">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="mr-3 p-2 rounded-full bg-gray-50 text-blockchain-primary">
                                <i class="fas fa-plus"></i>
                            </div>
                            <div>
                                <h5 class="text-sm font-medium text-gray-800">Create New Stream</h5>
                                <p class="text-xs text-gray-600 mt-1">
                                    Create a new blockchain stream
                                </p>
                            </div>
                        </div>
                        <i class="fas fa-chevron-right text-gray-300"></i>
                    </div>
                </a>
                
                <?php if (multichain_has_smart_filters()) { ?>
                    <a href="./?chain=<?php echo html($_GET['chain']) ?>&page=streamfilter" class="block p-4 hover:bg-gray-50 transition-all">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="mr-3 p-2 rounded-full bg-gray-50 text-blockchain-primary">
                                    <i class="fas fa-filter"></i>
                                </div>
                                <div>
                                    <h5 class="text-sm font-medium text-gray-800">Stream Filters</h5>
                                    <p class="text-xs text-gray-600 mt-1">
                                        Create or manage stream filters
                                    </p>
                                </div>
                            </div>
                            <i class="fas fa-chevron-right text-gray-300"></i>
                        </div>
                    </a>
                <?php } ?>
            </div>
        </div>
    </div>
</div>