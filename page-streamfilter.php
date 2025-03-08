<?php

require_once 'functions-filter.php';

$success = false; // set default value

if (@$_POST['teststreamfiltercode']) {
	if (no_displayed_error_result($teststreamfilter, multichain('teststreamfilter', false, $_POST['code']))) {
		if ($teststreamfilter['compiled'])
			output_success_text('Filter code successfully compiled');
		else
			output_error_text('Filter code failed to compile:' . "\n" . $teststreamfilter['reason']);
	}
}

if (@$_POST['createstreamfilter']) {
	$success = no_displayed_error_result($createtxid, multichain(
		'createfrom',
		$_POST['createfrom'],
		'streamfilter',
		$_POST['name'],
		false,
		$_POST['code']
	));

	if ($success)
		output_success_text('Filter successfully created in transaction ' . $createtxid);
}

if (@$_POST['teststreamfilterpublish']) {
	if ($_POST['format'] == 'json') {
		$json = json_decode($_POST['data']);

		if ($json === null) {
			output_html_error('The entered JSON structure does not appear to be valid');
			$data = null;
		} else
			$data = array('json' => $json);

	} elseif ($_POST['format'] == 'text')
		$data = array('text' => $_POST['data']);
	else
		$data = trim($_POST['data']);

	if (
		isset($data) && no_displayed_error_result($createrawsendfrom, multichain(
			'createrawsendfrom',
			$_POST['sendfrom'],
			new stdClass(),
			array(
				array(
					'for' => $_POST['stream'],
					'keys' => preg_split('/\n|\r\n?/', trim($_POST['keys'])),
					'data' => $data,
					'options' => $_POST['offchain'] ? 'offchain' : ''
				)
			),
			'sign'
		))
	) {

		if (
			no_displayed_error_result($teststreamfilter, multichain_with_raw(
				$teststreamfilterraw,
				'teststreamfilter',
				false,
				$_POST['code'],
				$createrawsendfrom['hex']
			))
		) {

			if ($teststreamfilter['compiled']) {
				$suffix = ' (time taken ' . number_format($teststreamfilter['time'], 6) . ' seconds)';

				if ($teststreamfilter['passed'])
					output_success_text('Filter code allowed this stream item' . $suffix);
				else
					output_error_text('Filter code blocked this stream item with the reason: ' . $suffix . "\n" . $teststreamfilter['reason']);

				if ($_POST['callbacks'])
					output_filter_test_callbacks($teststreamfilterraw);

			} else
				output_error_text('Filter code failed to compile:' . "\n" . $teststreamfilter['reason']);
		}
	}
}

$filterkeystreams = array();

if (no_displayed_error_result($liststreams, multichain('liststreams', '*', true)))
	foreach ($liststreams as $stream) foreach ($stream['filters'] as $streamfilter)
			$filterkeystreams[$streamfilter['createtxid']][$stream['createtxid']] = $stream['name'];

$labels = multichain_labels();

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

	if (
		no_displayed_error_result(
			$listpermissions,
			multichain('listpermissions', 'create', implode(',', array_get_column($getaddresses, 'address')))
		)
	)
		$createaddresses = array_unique(array_get_column($listpermissions, 'address'));
}

?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-8">
    <!-- Left Column: Existing Stream Filters List -->
    <div class="md:col-span-1">
        <div class="bg-white rounded-xl shadow-card overflow-hidden">
            <!-- Header -->
            <div class="bg-blockchain-dark px-5 py-4 flex items-center justify-between relative">
                <h3 class="text-white text-lg font-semibold m-0 flex items-center">
                    <i class="fas fa-filter mr-2"></i>
                    Stream Filters
                </h3>
                <?php 
                if (isset($liststreamfilters) && is_array($liststreamfilters)) {
                    echo '<span class="bg-white/20 text-white text-xs py-1 px-3 rounded-full">' . count($liststreamfilters) . ' filters</span>';
                }
                ?>
                <!-- Bottom border accent with gradient -->
                <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-blockchain-primary to-blockchain-secondary"></div>
            </div>
            
            <!-- Filters List -->
            <div class="divide-y divide-gray-100 max-h-[600px] overflow-y-auto">
                <?php
                if (no_displayed_error_result($liststreamfilters, multichain('liststreamfilters', '*', true))) {
                    if (count($liststreamfilters) > 0) {
                        foreach ($liststreamfilters as $filter) {
                            $name = $filter['name'];
                            ?>
                            <div class="p-4 hover:bg-gray-50 transition-colors <?php echo ($success && ($name == @$_POST['name'])) ? 'bg-green-50' : '' ?>">
                                <!-- Filter Header with Name -->
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="font-medium text-gray-800 m-0 flex items-center">
                                        <i class="fas fa-filter text-blockchain-primary mr-2"></i>
                                        <?php echo html($name) ?>
                                    </h4>
                                    <?php if ($success && ($name == @$_POST['name'])) { ?>
                                        <span class="bg-green-100 text-green-800 text-xs font-medium py-1 px-2 rounded-full">
                                            <i class="fas fa-check-circle mr-1"></i> New
                                        </span>
                                    <?php } ?>
                                </div>
                                
                                <!-- Filter Details -->
                                <div class="space-y-3 text-sm">
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-500">Code Language:</span>
                                        <span class="font-medium px-2 py-1 bg-gray-100 rounded text-gray-700">
                                            <?php echo html(ucfirst($filter['language'])) ?>
                                        </span>
                                    </div>
                                    
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-500">Code Size:</span>
                                        <a href="./filter-code.php?chain=<?php echo html($_GET['chain']) ?>&txid=<?php echo html($filter['createtxid']) ?>" 
                                            class="text-blockchain-primary hover:underline flex items-center">
                                            <i class="fas fa-code mr-1"></i>
                                            <?php echo number_format($filter['codelength']) ?> bytes
                                        </a>
                                    </div>
                                    
                                    <div>
                                        <div class="text-gray-500 mb-1">Active on streams:</div>
                                        <?php
                                        if (@count($filterkeystreams[$filter['createtxid']])) {
                                            echo '<div class="flex flex-wrap gap-2">';
                                            foreach ($filterkeystreams[$filter['createtxid']] as $streamcreatetxid => $streamname) {
                                                echo '<span class="inline-block px-2 py-1 bg-blue-50 text-blue-800 text-xs rounded-lg">' . 
                                                    html(strlen($streamname) ? $streamname : $streamcreatetxid) . 
                                                '</span>';
                                            }
                                            echo '</div>';
                                        } else {
                                            echo '<p class="text-gray-500 italic text-xs">No active streams</p>';
                                        }
                                        ?>
                                        <a href="./?chain=<?php echo html($_GET['chain']) ?>&page=approve&streamfilter=<?php echo html($filter['createtxid']) ?>" 
                                            class="mt-2 text-xs text-blockchain-primary hover:underline inline-flex items-center">
                                            <i class="fas fa-sliders-h mr-1"></i> Manage stream permissions
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        ?>
                        <div class="p-8 text-center">
                            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 text-gray-500 mb-3">
                                <i class="fas fa-filter"></i>
                            </div>
                            <p class="text-gray-500">No stream filters created yet</p>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Right Column: Create/Test Stream Filter -->
    <div class="md:col-span-2">
        <div class="bg-white rounded-xl shadow-card overflow-hidden mb-8">
            <!-- Header section -->
            <div class="bg-gradient-primary px-6 py-4 relative">
                <div class="flex items-center relative z-10">
                    <div class="mr-4 p-3 rounded-full bg-white/20 border border-white/30 shadow-lg">
                        <i class="fas fa-code text-white text-xl"></i>
                    </div>
                    <h3 class="text-white text-xl font-semibold m-0">Create & Test Stream Filter</h3>
                </div>
                <!-- Decorative pattern overlay -->
                <div class="absolute inset-0 opacity-10" 
                    style="background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCA2MCA2MCIgd2lkdGg9IjYwIiBoZWlnaHQ9IjYwIj48cGF0aCBkPSJNMzAgNUw1IDE3LjVWNDIuNUwzMCA1NUw1NSA0Mi41VjE3LjVMMzAgNVpNMzAgMjBMMTUgMjcuNVYxMi41TDMwIDIwWk0zNSAxMi41VjI3LjVMMjAgMzVMMzUgNDIuNVYyNy41TDUwIDIwTDM1IDEyLjVaIiBmaWxsPSIjZmZmIi8+PC9zdmc+');">
                </div>
            </div>

            <!-- Form Content -->
            <div class="px-6 py-6">
                <!-- Filter Code Editor Section -->
                <div class="mb-8" x-data="{ showInfo: true }">
                    <div x-show="showInfo" class="mb-5 bg-blue-50 rounded-lg p-4 border border-blue-100">
                        <div class="flex">
                            <div class="flex-shrink-0 text-blue-500">
                                <i class="fas fa-info-circle text-xl"></i>
                            </div>
                            <div class="ml-3">
                                <div class="flex justify-between">
                                    <h3 class="text-sm font-medium text-blue-800">About Stream Filters</h3>
                                    <button type="button" @click="showInfo = false" class="text-blue-500 hover:text-blue-700">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <div class="mt-2 text-sm text-blue-700">
                                    <p>Stream filters allow you to validate data before it's published to a stream. Benefits include:</p>
                                    <ul class="list-disc mt-2 pl-5 space-y-1">
                                        <li>Enforcing data structure and integrity</li>
                                        <li>Preventing invalid or malicious data</li>
                                        <li>Implementing custom business logic</li>
                                    </ul>
                                    <p class="mt-2">Filters are written in JavaScript and run automatically on publish attempts.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <form method="post" action="./?chain=<?php echo html($_GET['chain']) ?>&page=<?php echo html($_GET['page']) ?>" class="space-y-8">
                        <!-- Code Editor Section -->
                        <div class="space-y-3">
                            <label for="code" class="block text-sm font-medium text-gray-700">Filter Code (JavaScript)</label>
                            <div class="relative">
                                <div class="absolute top-0 right-0 bg-gray-50 border-l border-b border-gray-200 rounded-bl px-2 py-1 text-xs text-gray-500">
                                    JavaScript
                                </div>
                                <textarea 
                                    class="blockchain-data w-full h-64 p-4 font-mono text-sm bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blockchain-primary focus:border-blockchain-primary" 
                                    name="code" 
                                    id="code"
                                    spellcheck="false"
                                ><?php if (strlen(@$_POST['code']))
                                    echo html($_POST['code']);
                                else {
                                    ?>function filterstreamitem()
{
    var item=getfilterstreamitem();

    if (item.keys.length<2)
        return "At least two keys required";
}<?php } ?></textarea>
                            </div>
                            <div class="flex">
                                <button type="submit" name="teststreamfiltercode" class="flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg border border-gray-200 transition-colors">
                                    <i class="fas fa-check-circle mr-2 text-green-500"></i>
                                    Test Compilation Only
                                </button>
                            </div>
                        </div>
                        
                        <!-- Horizontal divider -->
                        <div class="border-t border-gray-100 my-6"></div>

                        <!-- Tab-like Navigation for Testing vs Creating -->
                        <div x-data="{ activeTab: 'test' }" class="space-y-6">
                            <div class="flex border-b border-gray-200">
                                <button 
                                    type="button"
                                    @click="activeTab = 'test'" 
                                    :class="{ 'text-blockchain-primary border-blockchain-primary': activeTab === 'test', 'text-gray-500 border-transparent': activeTab !== 'test' }"
                                    class="py-2 px-4 border-b-2 font-medium text-sm focus:outline-none"
                                >
                                    <i class="fas fa-vial mr-2"></i> Test with Sample Data
                                </button>
                                <button 
                                    type="button"
                                    @click="activeTab = 'create'" 
                                    :class="{ 'text-blockchain-primary border-blockchain-primary': activeTab === 'create', 'text-gray-500 border-transparent': activeTab !== 'create' }"
                                    class="py-2 px-4 border-b-2 font-medium text-sm focus:outline-none"
                                >
                                    <i class="fas fa-save mr-2"></i> Create Filter
                                </button>
                            </div>
                            
                            <!-- Test Filter Panel -->
                            <div x-show="activeTab === 'test'" class="space-y-6">
                                <div class="p-4 bg-yellow-50 border border-yellow-100 rounded-lg">
                                    <h4 class="text-sm font-medium text-yellow-800 flex items-center mb-2">
                                        <i class="fas fa-flask mr-2"></i>
                                        Test Environment
                                    </h4>
                                    <p class="text-xs text-yellow-700">
                                        Define test parameters below to simulate a stream publish operation and see how your filter responds.
                                    </p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Test Address -->
                                    <div>
                                        <label for="sendfrom" class="block text-sm font-medium text-gray-700 mb-1">
                                            Test Publish Address
                                        </label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="fas fa-user-circle text-gray-400"></i>
                                            </div>
                                            <select class="blockchain-data block w-full pl-10 pr-10 py-2 text-gray-700 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blockchain-primary focus:border-blockchain-primary appearance-none" 
                                                name="sendfrom" 
                                                id="sendfrom"
                                            >
                                                <?php
                                                foreach ($sendaddresses as $address) {
                                                    echo '<option value="' . html($address) . '"' . 
                                                        ((@$_POST['sendfrom'] == $address) ? ' selected' : '') . '>' . 
                                                        format_address_html($address, true, $labels) . '</option>';
                                                }
                                                ?>
                                            </select>
                                            <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
                                                <i class="fas fa-chevron-down text-gray-400"></i>
                                            </div>
                                        </div>
                                        <p class="mt-1 text-xs text-gray-500">Address that will be used for test publishing</p>
                                    </div>
                                    
                                    <!-- Test Stream -->
                                    <div>
                                        <label for="stream" class="block text-sm font-medium text-gray-700 mb-1">
                                            Target Stream
                                        </label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="fas fa-stream text-gray-400"></i>
                                            </div>
                                            <select class="blockchain-data block w-full pl-10 pr-10 py-2 text-gray-700 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blockchain-primary focus:border-blockchain-primary appearance-none" 
                                                name="stream" 
                                                id="stream"
                                            >
                                                <?php
                                                foreach ($liststreams as $stream) {
                                                    echo '<option value="' . html($stream['createtxid']) . '"' . 
                                                        ((@$_POST['stream'] == $stream['createtxid']) ? ' selected' : '') . '>' . 
                                                        html($stream['name']) . '</option>';
                                                }
                                                ?>
                                            </select>
                                            <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
                                                <i class="fas fa-chevron-down text-gray-400"></i>
                                            </div>
                                        </div>
                                        <div class="mt-1 flex items-center">
                                            <input type="checkbox" id="offchain" name="offchain" value="1" <?php echo @$_POST['offchain'] ? 'checked' : '' ?> 
                                                class="h-4 w-4 text-blockchain-primary focus:ring-blockchain-primary border-gray-300 rounded">
                                            <label for="offchain" class="ml-2 block text-xs text-gray-500">
                                                Publish as off-chain item
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Item Keys -->
                                <div>
                                    <label for="keys" class="block text-sm font-medium text-gray-700 mb-1">
                                        Stream Item Keys
                                    </label>
                                    <textarea 
                                        class="w-full p-3 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blockchain-primary focus:border-blockchain-primary font-mono text-sm" 
                                        rows="3" 
                                        name="keys" 
                                        id="keys"
                                        placeholder="Enter one key per line"
                                    ><?php echo html(@$_POST['keys']) ?></textarea>
                                    <p class="mt-1 text-xs text-gray-500">Each line represents a separate key for the stream item</p>
                                </div>
                                
                                <!-- Data Format & Content -->
                                <div>
                                    <div class="mb-3">
                                        <label for="format" class="block text-sm font-medium text-gray-700 mb-1">
                                            Data Format
                                        </label>
                                        <select class="blockchain-data block w-full py-2 px-3 text-gray-700 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blockchain-primary focus:border-blockchain-primary" 
                                            name="format" 
                                            id="format"
                                        >
                                            <option value="">Raw binary (enter hexadecimal)</option>
                                            <option value="text" <?php echo (@$_POST['format'] == 'text') ? ' selected' : '' ?>>Text</option>
                                            <option value="json" <?php echo (@$_POST['format'] == 'json') ? ' selected' : '' ?>>JSON</option>
                                        </select>
                                    </div>
                                    
                                    <label for="data" class="block text-sm font-medium text-gray-700 mb-1">
                                        Data Content
                                    </label>
                                    <textarea 
                                        class="w-full p-3 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blockchain-primary focus:border-blockchain-primary font-mono text-sm" 
                                        rows="6" 
                                        name="data" 
                                        id="data"
                                        placeholder="Enter data in the selected format"
                                    ><?php echo html(@$_POST['data']) ?></textarea>
                                </div>
                                
                                <div class="flex space-x-3 items-center">
                                    <button type="submit" name="teststreamfilterpublish" class="flex items-center px-6 py-3 bg-gradient-button text-white font-medium rounded-lg hover:shadow-lg transition-all">
                                        <i class="fas fa-vial mr-2"></i>
                                        Test Filter with Sample Data
                                    </button>
                                    
                                    <div class="flex items-center space-x-2">
                                        <input type="checkbox" id="callbacks" name="callbacks" value="1" <?php echo @$_POST['callbacks'] ? 'checked' : '' ?> 
                                            class="h-4 w-4 text-blockchain-primary focus:ring-blockchain-primary border-gray-300 rounded">
                                        <label for="callbacks" class="text-sm text-gray-600">
                                            Display callback results
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Create Filter Panel -->
                            <div x-show="activeTab === 'create'" class="space-y-6">
                                <div class="p-4 bg-blue-50 border border-blue-100 rounded-lg">
                                    <h4 class="text-sm font-medium text-blue-800 flex items-center mb-2">
                                        <i class="fas fa-info-circle mr-2"></i>
                                        Filter Creation Information
                                    </h4>
                                    <p class="text-xs text-blue-700">
                                        Once created, your filter can be applied to one or more streams. You'll need to have create permissions on the blockchain.
                                    </p>
                                </div>
                                
                                <!-- From Address -->
                                <div>
                                    <label for="createfrom" class="block text-sm font-medium text-gray-700 mb-1">
                                        Create From Address
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-key text-gray-400"></i>
                                        </div>
                                        <select class="blockchain-data block w-full pl-10 pr-10 py-2 text-gray-700 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blockchain-primary focus:border-blockchain-primary appearance-none" 
                                            name="createfrom" 
                                            id="createfrom"
                                        >
                                            <?php
                                            foreach ($createaddresses as $address) {
                                                echo '<option value="' . html($address) . '"' . 
                                                    ((@$_POST['createfrom'] == $address) ? ' selected' : '') . '>' . 
                                                    format_address_html($address, true, $labels) . '</option>';
                                            }
                                            ?>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
                                            <i class="fas fa-chevron-down text-gray-400"></i>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Filter Name -->
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Filter Name</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-filter text-gray-400"></i>
                                        </div>
                                        <input 
                                            class="blockchain-data block w-full pl-10 py-2 text-gray-700 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blockchain-primary focus:border-blockchain-primary" 
                                            type="text" 
                                            name="name" 
                                            id="name" 
                                            placeholder="my-stream-filter" 
                                            value="<?php echo html(@$_POST['name']) ?>"
                                            pattern="[A-Za-z0-9._-]+" 
                                            title="Alphanumeric characters, dashes, periods and underscores only"
                                        >
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500">Use alphanumeric characters, dashes, periods, and underscores only</p>
                                </div>
                                
                                <!-- Submit Button -->
                                <button type="submit" name="createstreamfilter" class="w-full flex justify-center items-center px-6 py-3 bg-gradient-button text-white font-medium rounded-lg hover:shadow-lg transition-all">
                                    <i class="fas fa-plus-circle mr-2"></i>
                                    Create Stream Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Quick Guide Card -->
        <div class="bg-white rounded-lg shadow-card border border-gray-100 p-5">
            <h4 class="text-gray-800 font-medium flex items-center mb-3">
                <i class="fas fa-lightbulb text-yellow-500 mr-2"></i>
                Working with Stream Filters
            </h4>
            <p class="text-gray-600 text-sm mb-4">
                Stream filters let you control what data can be published to streams:
            </p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-gray-50 p-3 rounded-lg border border-gray-200">
                    <h5 class="text-sm font-medium text-gray-700 mb-2">Available JavaScript Functions</h5>
                    <ul class="text-xs text-gray-600 space-y-1">
                        <li><code class="bg-gray-100 px-1 py-0.5 rounded">getfilterstreamitem()</code> - Get the item being filtered</li>
                        <li><code class="bg-gray-100 px-1 py-0.5 rounded">getfiltertxid()</code> - Get the transaction ID</li>
                        <li><code class="bg-gray-100 px-1 py-0.5 rounded">getfilterassetid()</code> - Get asset ID if present</li>
                        <li><code class="bg-gray-100 px-1 py-0.5 rounded">getfiltertxdetails()</code> - Get transaction details</li>
                    </ul>
                </div>
                <div class="bg-gray-50 p-3 rounded-lg border border-gray-200">
                    <h5 class="text-sm font-medium text-gray-700 mb-2">Filter Return Values</h5>
                    <ul class="text-xs text-gray-600 space-y-1">
                        <li><code class="bg-gray-100 px-1 py-0.5 rounded">undefined</code> or <code class="bg-gray-100 px-1 py-0.5 rounded">null</code> - Allow the item</li>
                        <li><code class="bg-gray-100 px-1 py-0.5 rounded">"error message"</code> - Block item with message</li>
                        <li>Return from the function to complete evaluation</li>
                        <li>Throw an exception to block with that message</li>
                    </ul>
                </div>
            </div>
            
            <div class="mt-4 p-3 bg-blue-50 rounded-lg border border-blue-100">
                <h5 class="text-sm font-medium text-blue-800 mb-2">Filter Code Example</h5>
                <pre class="bg-white p-2 rounded border border-blue-50 overflow-x-auto text-xs text-blue-900">function filterstreamitem() {
  var item = getfilterstreamitem();
  
  // Validate JSON structure
  if (item.format === 'json') {
    if (!item.data.name || !item.data.value)
      return "JSON must contain name and value fields";
      
    if (typeof item.data.value !== 'number')
      return "Value field must be a number";
  }
}</pre>
            </div>
        </div>
    </div>
</div>