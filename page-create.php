<?php

$success = false; // set default value

if (@$_POST['createstream']) {
	$success = no_displayed_error_result($createtxid, multichain(
		'createfrom',
		$_POST['from'],
		'stream',
		$_POST['name'],
		true
	));

	if ($success)
		output_success_text('Stream successfully created in transaction ' . $createtxid);
}

$labels = multichain_labels();

if (no_displayed_error_result($getaddresses, multichain('getaddresses', true))) {
	foreach ($getaddresses as $index => $address)
		if (!$address['ismine'])
			unset($getaddresses[$index]);

	if (
		no_displayed_error_result(
			$listpermissions,
			multichain('listpermissions', 'create', implode(',', array_get_column($getaddresses, 'address')))
		)
	)
		$createaddresses = array_unique(array_get_column($listpermissions, 'address'));
}

no_displayed_error_result($liststreams, multichain('liststreams', '*', true));

?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-8">
    <!-- Left Column: Create Stream -->
    <div class="md:col-span-2">
        <div class="bg-white rounded-xl shadow-card overflow-hidden mb-8">
            <!-- Header section -->
            <div class="bg-gradient-primary px-6 py-4 relative">
                <div class="flex items-center relative z-10">
                    <div class="mr-4 p-3 rounded-full bg-white/20 border border-white/30 shadow-lg">
                        <i class="fas fa-plus-circle text-white text-xl"></i>
                    </div>
                    <h3 class="text-white text-xl font-semibold m-0">Create New Stream</h3>
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
                            <h3 class="text-sm font-medium text-blue-800">About Blockchain Streams</h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <p>Streams allow you to publish and store structured data on the blockchain. They are ideal for:</p>
                                <ul class="list-disc mt-2 pl-5 space-y-1">
                                    <li>Recording transactions or events</li>
                                    <li>Storing documents or data with timestamps</li>
                                    <li>Creating an immutable audit trail</li>
                                </ul>
                                <p class="mt-2">In this demo, all streams are created as open streams that anyone can write to.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <form method="post" action="./?chain=<?php echo html($_GET['chain']) ?>&page=<?php echo html($_GET['page']) ?>">
                    <!-- From Address Field -->
                    <div class="mb-6">
                        <label for="from" class="block text-sm font-medium text-gray-700 mb-1">
                            From address <span class="text-xs text-gray-500">(with create permissions)</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-key text-gray-400"></i>
                            </div>
                            <select class="blockchain-data block w-full pl-10 pr-10 py-3 text-gray-700 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blockchain-primary focus:border-blockchain-primary appearance-none" name="from" id="from">
                                <?php
                                foreach ($createaddresses as $address) {
                                    ?>
                                    <option value="<?php echo html($address) ?>">
                                        <?php echo format_address_html($address, true, $labels) ?>
                                    </option>
                                    <?php
                                }
                                ?>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
                                <i class="fas fa-chevron-down text-gray-400"></i>
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">This address must have stream creation permissions on the blockchain</p>
                    </div>
                    
                    <!-- Stream Name Field -->
                    <div class="mb-6">
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Stream name</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-stream text-gray-400"></i>
                            </div>
                            <input class="blockchain-data block w-full pl-10 py-3 text-gray-700 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blockchain-primary focus:border-blockchain-primary" 
                                type="text" name="name" id="name" placeholder="my-stream-name"
                                pattern="[A-Za-z0-9._-]+" title="Alphanumeric characters, dashes, periods and underscores only">
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Use alphanumeric characters, dashes, periods, and underscores only</p>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="mt-8">
                        <button type="submit" name="createstream" class="w-full flex justify-center items-center px-6 py-3 bg-gradient-button text-white font-medium rounded-lg hover:shadow-lg transition-all">
                            <i class="fas fa-plus-circle mr-2"></i>
                            Create Stream
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Quick Guide Card -->
        <div class="bg-white rounded-lg shadow-card border border-gray-100 p-5">
            <h4 class="text-gray-800 font-medium flex items-center mb-3">
                <i class="fas fa-lightbulb text-yellow-500 mr-2"></i>
                What's Next After Creating a Stream?
            </h4>
            <p class="text-gray-600 text-sm mb-4">
                After creating your stream, you can publish data to it or view its contents:
            </p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <a href="./?chain=<?php echo html($_GET['chain']) ?>&page=publish" class="flex items-center p-3 bg-gray-50 hover:bg-blue-50 rounded-lg border border-gray-200 hover:border-blockchain-primary transition-all no-underline text-gray-700 hover:text-blockchain-primary">
                    <i class="fas fa-upload text-blockchain-primary mr-3"></i>
                    <span>Publish to Stream</span>
                </a>
                <a href="./?chain=<?php echo html($_GET['chain']) ?>&page=view" class="flex items-center p-3 bg-gray-50 hover:bg-blue-50 rounded-lg border border-gray-200 hover:border-blockchain-primary transition-all no-underline text-gray-700 hover:text-blockchain-primary">
                    <i class="fas fa-search text-blockchain-primary mr-3"></i>
                    <span>View Stream Items</span>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Right Column: Existing Streams -->
    <div class="md:col-span-1">
        <div class="bg-white rounded-xl shadow-card overflow-hidden">
            <!-- Header -->
            <div class="bg-blockchain-dark px-5 py-4 flex items-center justify-between relative">
                <h3 class="text-white text-lg font-semibold m-0 flex items-center">
                    <i class="fas fa-stream mr-2"></i>
                    Existing Streams
                </h3>
                <span class="bg-white/20 text-white text-xs py-1 px-3 rounded-full"><?php echo count($liststreams); ?> streams</span>
                <!-- Bottom border accent with gradient -->
                <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-blockchain-primary to-blockchain-secondary"></div>
            </div>
            
            <!-- Streams List -->
            <div class="divide-y divide-gray-100 max-h-[600px] overflow-y-auto">
                <?php foreach ($liststreams as $stream) { ?>
                    <div class="p-4 hover:bg-gray-50 transition-colors <?php echo ($success && ($stream['name'] == @$_POST['name'])) ? 'bg-green-50' : '' ?>">
                        <!-- Stream Header with Name -->
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="font-medium text-gray-800 m-0 flex items-center">
                                <span class="w-2 h-2 rounded-full <?php echo ($stream['subscribed']) ? 'bg-green-500' : 'bg-gray-400' ?> mr-2"></span>
                                <?php echo html($stream['name']) ?>
                            </h4>
                            <?php if ($success && ($stream['name'] == @$_POST['name'])) { ?>
                                <span class="bg-green-100 text-green-800 text-xs font-medium py-1 px-2 rounded-full">
                                    <i class="fas fa-check-circle mr-1"></i> New
                                </span>
                            <?php } ?>
                        </div>
                        
                        <!-- Stream Details -->
                        <div class="grid grid-cols-2 gap-2 mt-3 text-sm">
                            <div class="col-span-2">
                                <span class="text-gray-500">Created by:</span>
                                <div class="hash-value mt-1">
                                    <?php echo format_address_html($stream['creators'][0], false, $labels) ?>
                                </div>
                            </div>
                            <div class="bg-gray-50 p-2 rounded border border-gray-100 text-center">
                                <span class="text-xs text-gray-500 block">Items</span>
                                <span class="blockchain-data font-medium text-gray-800">
                                    <?php
                                    if ($stream['subscribed']) {
                                        echo html($stream['items']);
                                    } else {
                                        echo '<i class="fas fa-minus text-gray-400"></i>';
                                    }
                                    ?>
                                </span>
                            </div>
                            <div class="bg-gray-50 p-2 rounded border border-gray-100 text-center">
                                <span class="text-xs text-gray-500 block">Status</span>
                                <span class="blockchain-data font-medium <?php echo ($stream['subscribed']) ? 'text-green-600' : 'text-gray-500' ?>">
                                    <?php echo ($stream['subscribed']) ? 'Subscribed' : 'Not Subscribed'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php } ?>
                
                <?php if (count($liststreams) === 0) { ?>
                    <div class="p-8 text-center">
                        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 text-gray-500 mb-3">
                            <i class="fas fa-empty-set"></i>
                        </div>
                        <p class="text-gray-500">No streams found on this blockchain</p>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>