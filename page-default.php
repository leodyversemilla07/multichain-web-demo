<?php
// Make sure we have the multichain function
if (!function_exists('multichain')) {
    if (file_exists('functions.php')) {
        require_once 'functions.php';
    } else {
        die('Required functions not available');
    }
}

if (@$_POST['getnewaddress'])
	no_displayed_error_result($getnewaddress, multichain('getnewaddress'));
?>

<div x-data="{ 
    showModal: false,
    activeTab: 'node',
    isLoading: true,
    connectionError: false
}" 
    x-init="setTimeout(() => { isLoading = false }, 600)" 
    class="space-y-8" 
    x-cloak>

    <!-- Connection Error Display -->
    <?php
    $hasConnectionError = false;
    $errorMessage = "";
    
    try {
        // Check if the function exists before calling it
        if (function_exists('multichain_getinfo')) {
            $getinfo = multichain_getinfo();
        } else {
            throw new Exception('MultiChain functions not available');
        }
    } catch (Exception $e) {
        $hasConnectionError = true;
        $errorMessage = $e->getMessage();
    }
    
    if ($hasConnectionError): 
    ?>
    <div class="bg-white rounded-xl shadow-lg overflow-hidden border-l-4 border-red-500 transform transition duration-300 animate-pulse">
        <div class="p-6">
            <div class="flex flex-col md:flex-row items-center">
                <!-- Error Icon with Animation -->
                <div class="mb-6 md:mb-0 md:mr-6">
                    <div class="relative">
                        <div class="absolute inset-0 bg-red-100 rounded-full animate-ping opacity-60"></div>
                        <div class="w-24 h-24 rounded-full bg-red-50 border border-red-200 flex items-center justify-center relative">
                            <i class="fas fa-exclamation-triangle text-red-500 text-4xl"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Error Content -->
                <div class="flex-1">
                    <h3 class="text-2xl font-bold text-gray-800 mb-3">Connection Error: Node Unavailable</h3>
                    <p class="text-gray-600 mb-4">
                        Unable to connect to the selected MultiChain node. The node might be offline or experiencing issues.
                    </p>
                    <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-4 rounded">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-code text-red-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-red-700 font-mono overflow-auto">
                                    <?php echo htmlspecialchars($errorMessage); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Troubleshooting Steps -->
                    <div class="mb-4">
                        <h4 class="text-lg font-medium text-gray-700 mb-2 flex items-center">
                            <i class="fas fa-tools mr-2 text-gray-500"></i> Troubleshooting Steps
                        </h4>
                        <ul class="space-y-2 text-gray-600">
                            <li class="flex items-start">
                                <div class="flex-shrink-0 w-5 h-5 rounded-full bg-gray-100 flex items-center justify-center mr-2 mt-0.5">
                                    <span class="text-xs font-medium">1</span>
                                </div>
                                <span>Verify that the MultiChain daemon is running on the server</span>
                            </li>
                            <li class="flex items-start">
                                <div class="flex-shrink-0 w-5 h-5 rounded-full bg-gray-100 flex items-center justify-center mr-2 mt-0.5">
                                    <span class="text-xs font-medium">2</span>
                                </div>
                                <span>Check if the RPC connection details in the config file are correct</span>
                            </li>
                            <li class="flex items-start">
                                <div class="flex-shrink-0 w-5 h-5 rounded-full bg-gray-100 flex items-center justify-center mr-2 mt-0.5">
                                    <span class="text-xs font-medium">3</span>
                                </div>
                                <span>Ensure the network has no connectivity issues</span>
                            </li>
                        </ul>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="flex flex-wrap gap-3">
                        <a href="<?php echo $_SERVER['REQUEST_URI']; ?>" class="px-4 py-2 bg-blockchain-primary text-white rounded-lg hover:bg-blockchain-dark transition-colors duration-200 flex items-center">
                            <i class="fas fa-sync-alt mr-2"></i> Retry Connection
                        </a>
                        <a href="./" class="px-4 py-2 border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-50 transition-colors duration-200 flex items-center">
                            <i class="fas fa-arrow-left mr-2"></i> Back to Node Selection
                        </a>
                        <button @click="showModal = true" class="px-4 py-2 border border-gray-300 bg-gray-50 text-gray-600 rounded-lg hover:bg-gray-100 transition-colors duration-200 flex items-center">
                            <i class="fas fa-question-circle mr-2"></i> Help
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Server Details (Collapsible) -->
        <div x-data="{ open: false }" class="border-t border-gray-200">
            <button @click="open = !open" class="w-full px-6 py-3 text-left bg-gray-50 hover:bg-gray-100 transition-colors duration-200 focus:outline-none flex justify-between items-center">
                <span class="font-medium text-gray-700 flex items-center">
                    <i class="fas fa-server mr-2 text-gray-500"></i> View Connection Details
                </span>
                <i class="fas" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
            </button>
            
            <div x-show="open" x-transition class="p-6 bg-gray-50">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-white p-4 rounded-lg border border-gray-200">
                        <h5 class="text-sm font-medium text-gray-700 mb-2">Node Information</h5>
                        <table class="w-full text-sm">
                            <tr>
                                <td class="py-1 text-gray-500">Chain Name:</td>
                                <td class="py-1 font-mono"><?php echo htmlspecialchars($chain); ?></td>
                            </tr>
                            <?php if (isset($config[$chain])): ?>
                            <tr>
                                <td class="py-1 text-gray-500">RPC Host:</td>
                                <td class="py-1 font-mono"><?php echo htmlspecialchars($config[$chain]['rpchost']); ?></td>
                            </tr>
                            <tr>
                                <td class="py-1 text-gray-500">RPC Port:</td>
                                <td class="py-1 font-mono"><?php echo htmlspecialchars($config[$chain]['rpcport']); ?></td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                    <div class="bg-white p-4 rounded-lg border border-gray-200">
                        <h5 class="text-sm font-medium text-gray-700 mb-2">Diagnostic Information</h5>
                        <div class="text-xs text-gray-600 font-mono bg-gray-50 p-2 rounded border border-gray-200 h-20 overflow-auto">
                            Error occurred at: <?php echo date('Y-m-d H:i:s'); ?><br>
                            PHP Version: <?php echo PHP_VERSION; ?><br>
                            Server: <?php echo htmlspecialchars($_SERVER['SERVER_SOFTWARE']); ?><br>
                            Request URI: <?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Help Modal -->
    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm z-40 flex items-center justify-center" 
         x-show="showModal" 
         x-cloak
         x-transition:enter="transition ease-out duration-300" 
         x-transition:enter-start="opacity-0" 
         x-transition:enter-end="opacity-100" 
         x-transition:leave="transition ease-in duration-200" 
         x-transition:leave-start="opacity-100" 
         x-transition:leave-end="opacity-0">
        
        <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl mx-4 overflow-hidden" 
             @click.outside="showModal = false"
             x-show="showModal" 
             x-cloak
             x-transition:enter="transition ease-out duration-300" 
             x-transition:enter-start="opacity-0 transform translate-y-4" 
             x-transition:enter-end="opacity-100 transform translate-y-0" 
             x-transition:leave="transition ease-in duration-200" 
             x-transition:leave-start="opacity-100 transform translate-y-0" 
             x-transition:leave-end="opacity-0 transform translate-y-4">
            
            <div class="bg-gradient-primary px-6 py-5 flex justify-between items-center">
                <h3 class="text-white text-lg font-medium flex items-center">
                    <i class="fas fa-question-circle mr-2"></i> MultiChain Connection Help
                </h3>
                <button type="button" @click="showModal = false" 
                        class="text-white hover:text-gray-200 focus:outline-none w-8 h-8 flex items-center justify-center rounded-full hover:bg-white/10 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="p-6">
                <h4 class="text-xl font-medium text-gray-800 mb-4">Common Connection Issues</h4>
                
                <div class="space-y-4 mb-6">
                    <div class="bg-gray-50 p-4 rounded-lg border-l-4 border-blockchain-primary">
                        <h5 class="text-lg font-medium text-gray-800 mb-2">Starting the MultiChain Daemon</h5>
                        <p class="text-gray-600 mb-3">Ensure that the MultiChain daemon is running on your server.</p>
                        <div class="bg-gray-900 text-green-400 p-3 rounded text-sm font-mono overflow-x-auto">
                            <p class="mb-1">multichaind [chain-name] -daemon</p>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded-lg border-l-4 border-blockchain-primary">
                        <h5 class="text-lg font-medium text-gray-800 mb-2">RPC Configuration</h5>
                        <p class="text-gray-600 mb-2">Check if the RPC configuration is correct in the following files:</p>
                        <ul class="list-disc list-inside text-gray-600 mb-3">
                            <li>multichain.conf</li>
                            <li>Your chain's params.dat file</li>
                            <li>The web demo's config.txt</li>
                        </ul>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded-lg border-l-4 border-blockchain-primary">
                        <h5 class="text-lg font-medium text-gray-800 mb-2">Firewall Settings</h5>
                        <p class="text-gray-600">Verify that the firewall allows connections to the RPC port.</p>
                    </div>
                </div>
                
                <div class="flex justify-between pt-4 border-t border-gray-200">
                    <a href="https://www.multichain.com/developers/" target="_blank" class="text-blockchain-primary hover:text-blockchain-dark flex items-center">
                        <i class="fas fa-external-link-alt mr-2"></i> MultiChain Documentation
                    </a>
                    <button @click="showModal = false" class="px-4 py-2 bg-blockchain-primary text-white rounded-lg hover:bg-blockchain-dark transition-colors">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <?php else: ?>
    <!-- Dashboard Stats Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <?php
        if (is_array($getinfo)) {
        ?>
            <!-- Block Height Card -->
            <div class="bg-white rounded-xl shadow-card overflow-hidden transform transition hover:shadow-card-hover hover:translate-y-[-2px] duration-300">
                <div class="px-5 py-4 bg-gradient-to-r from-blockchain-primary/10 to-blockchain-primary/5">
                    <div class="flex justify-between items-center">
                        <p class="text-sm font-medium text-gray-600 m-0">Block Height</p>
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-blockchain-primary text-white">
                            <i class="fas fa-layer-group"></i>
                        </div>
                    </div>
                    <div class="mt-3 mb-1">
                        <h3 class="text-2xl font-mono font-bold blockchain-data">
                            <span x-show="isLoading" class="inline-block w-24 h-8 bg-gray-200 animate-pulse rounded"></span>
                            <span x-show="!isLoading" x-text="'<?php echo html($getinfo['blocks']) ?>'"></span>
                        </h3>
                    </div>
                    <p class="text-sm text-gray-500 flex items-center m-0">
                        <i class="fas fa-clock mr-1 text-blockchain-primary"></i> Latest Block
                    </p>
                </div>
                <div class="px-5 py-3 bg-white border-t border-gray-100">
                    <a href="#" class="text-blockchain-primary text-sm font-medium hover:text-blockchain-dark flex items-center">
                        View Details <i class="fas fa-chevron-right ml-2 text-xs"></i>
                    </a>
                </div>
            </div>

            <!-- Connected Peers Card -->
            <div class="bg-white rounded-xl shadow-card overflow-hidden transform transition hover:shadow-card-hover hover:translate-y-[-2px] duration-300">
                <div class="px-5 py-4 bg-gradient-to-r from-blue-500/10 to-blue-500/5">
                    <div class="flex justify-between items-center">
                        <p class="text-sm font-medium text-gray-600 m-0">Connected Peers</p>
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-blue-500 text-white">
                            <i class="fas fa-network-wired"></i>
                        </div>
                    </div>
                    <div class="mt-3 mb-1">
                        <h3 class="text-2xl font-mono font-bold blockchain-data">
                            <span x-show="isLoading" class="inline-block w-24 h-8 bg-gray-200 animate-pulse rounded"></span>
                            <span x-show="!isLoading" class="flex items-center">
                                <?php echo html($getinfo['connections']) ?>
                                <?php if ($getinfo['connections'] > 0): ?>
                                    <span class="ml-2 px-2 py-0.5 bg-green-500 text-white text-xs rounded-full flex items-center">
                                        <span class="w-2 h-2 bg-white rounded-full mr-1 animate-ping"></span>
                                        Online
                                    </span>
                                <?php else: ?>
                                    <span class="ml-2 px-2 py-0.5 bg-yellow-500 text-white text-xs rounded-full">No Peers</span>
                                <?php endif; ?>
                            </span>
                        </h3>
                    </div>
                    <p class="text-sm text-gray-500 flex items-center m-0">
                        <i class="fas fa-server mr-1 text-blue-500"></i> Network Status
                    </p>
                </div>
                <div class="px-5 py-3 bg-white border-t border-gray-100">
                    <a href="#" class="text-blue-500 text-sm font-medium hover:text-blue-700 flex items-center">
                        Check Network <i class="fas fa-chevron-right ml-2 text-xs"></i>
                    </a>
                </div>
            </div>

            <!-- Protocol Version Card -->
            <div class="bg-white rounded-xl shadow-card overflow-hidden transform transition hover:shadow-card-hover hover:translate-y-[-2px] duration-300">
                <div class="px-5 py-4 bg-gradient-to-r from-purple-500/10 to-purple-500/5">
                    <div class="flex justify-between items-center">
                        <p class="text-sm font-medium text-gray-600 m-0">Node Version</p>
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-purple-500 text-white">
                            <i class="fas fa-code-branch"></i>
                        </div>
                    </div>
                    <div class="mt-3 mb-1">
                        <h3 class="text-2xl font-mono font-bold blockchain-data">
                            <span x-show="isLoading" class="inline-block w-24 h-8 bg-gray-200 animate-pulse rounded"></span>
                            <span x-show="!isLoading" x-text="'<?php echo html($getinfo['version']) ?>'"></span>
                        </h3>
                    </div>
                    <p class="text-sm text-gray-500 flex items-center m-0">
                        <i class="fas fa-sitemap mr-1 text-purple-500"></i> Protocol: <?php echo html($getinfo['protocolversion']) ?>
                    </p>
                </div>
                <div class="px-5 py-3 bg-white border-t border-gray-100">
                    <a href="#" class="text-purple-500 text-sm font-medium hover:text-purple-700 flex items-center">
                        Version Info <i class="fas fa-chevron-right ml-2 text-xs"></i>
                    </a>
                </div>
            </div>
        <?php } ?>
    </div>

    <!-- Main Content Tabs -->
    <div class="bg-white rounded-xl shadow-card overflow-hidden mb-6">
        <div class="border-b border-gray-200">
            <ul class="flex flex-wrap -mb-px text-sm font-medium">
                <li class="mr-2">
                    <button @click="activeTab = 'node'" :class="{ 'border-blockchain-primary text-blockchain-primary': activeTab === 'node', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'node' }" class="inline-flex items-center py-4 px-4 border-b-2 rounded-t-lg">
                        <i class="fas fa-server mr-2"></i> Node Information
                    </button>
                </li>
                <li class="mr-2">
                    <button @click="activeTab = 'peers'" :class="{ 'border-blockchain-primary text-blockchain-primary': activeTab === 'peers', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'peers' }" class="inline-flex items-center py-4 px-4 border-b-2 rounded-t-lg">
                        <i class="fas fa-project-diagram mr-2"></i> Connected Peers
                    </button>
                </li>
            </ul>
        </div>
        
        <!-- Node Info Tab Content -->
        <div x-show="activeTab === 'node'">
            <div class="p-6">
                <?php
                if (is_array($getinfo)) {
                ?>
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <tbody>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-3 px-4 bg-gray-50 w-1/3">
                                        <i class="fas fa-signature text-blockchain-primary mr-2"></i> Chain Name
                                    </th>
                                    <td class="py-3 px-4">
                                        <div class="flex items-center">
                                            <span class="blockchain-data font-medium"><?php echo html($getinfo['chainname']) ?></span>
                                            <span class="ml-3 px-3 py-1 bg-gradient-to-r from-blockchain-primary to-blockchain-secondary text-white text-xs rounded-full">
                                                <?php echo html($getinfo['description']) ?>
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-3 px-4 bg-gray-50">
                                        <i class="fas fa-code-branch text-blockchain-primary mr-2"></i> Version
                                    </th>
                                    <td class="py-3 px-4">
                                        <span class="blockchain-data"><?php echo html($getinfo['version']) ?></span>
                                    </td>
                                </tr>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-3 px-4 bg-gray-50">
                                        <i class="fas fa-sitemap text-blockchain-primary mr-2"></i> Protocol
                                    </th>
                                    <td class="py-3 px-4">
                                        <span class="blockchain-data"><?php echo html($getinfo['protocolversion']) ?></span>
                                    </td>
                                </tr>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-3 px-4 bg-gray-50">
                                        <i class="fas fa-network-wired text-blockchain-primary mr-2"></i> Node Address
                                    </th>
                                    <td class="py-3 px-4">
                                        <div class="flex items-center">
                                            <span class="hash-value"><?php echo html($getinfo['nodeaddress']) ?></span>
                                            <button type="button" class="ml-3 p-1 rounded text-gray-500 hover:bg-gray-100 hover:text-blockchain-primary transition-colors copy-data" data-content="<?php echo html($getinfo['nodeaddress']) ?>" title="Copy to clipboard">
                                                <i class="far fa-copy"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-3 px-4 bg-gray-50">
                                        <i class="fas fa-layer-group text-blockchain-primary mr-2"></i> Blocks
                                    </th>
                                    <td class="py-3 px-4">
                                        <div class="flex items-center">
                                            <span class="blockchain-data"><?php echo html($getinfo['blocks']) ?></span>
                                            <span class="ml-2 px-2 py-1 bg-blockchain-primary text-white text-xs rounded-md">Latest</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-3 px-4 bg-gray-50">
                                        <i class="fas fa-users text-blockchain-primary mr-2"></i> Connected Peers
                                    </th>
                                    <td class="py-3 px-4">
                                        <div class="flex items-center">
                                            <span class="blockchain-data"><?php echo html($getinfo['connections']) ?></span>
                                            <?php if ($getinfo['connections'] > 0): ?>
                                                <span class="ml-2 px-2 py-1 bg-green-500 text-white text-xs rounded-md flex items-center">
                                                    <span class="w-1.5 h-1.5 bg-white rounded-full mr-1 animate-pulse"></span>
                                                    Online
                                                </span>
                                            <?php else: ?>
                                                <span class="ml-2 px-2 py-1 bg-yellow-500 text-white text-xs rounded-md">No Peers</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="text-left py-3 px-4 bg-gray-50">
                                        <i class="fas fa-server text-blockchain-primary mr-2"></i> Node Status
                                    </th>
                                    <td class="py-3 px-4">
                                        <div class="flex items-center">
                                            <div class="w-32 bg-gray-200 rounded-full h-2.5 mr-2">
                                                <div class="bg-green-500 h-2.5 rounded-full" style="width: 100%"></div>
                                            </div>
                                            <span class="text-sm text-gray-800">Running</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                <?php } ?>
            </div>
        </div>
        
        <!-- Connected Peers Tab Content -->
        <div x-show="activeTab === 'peers'" x-cloak>
            <div class="p-6">
                <?php if (no_displayed_error_result($peerinfo, multichain('getpeerinfo'))) { ?>
                    <?php if (count($peerinfo) > 0): ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php foreach ($peerinfo as $peer): ?>
                                <div class="bg-white border border-gray-200 hover:border-blockchain-primary transition-colors rounded-lg overflow-hidden shadow-sm hover:shadow-md">
                                    <div class="p-4">
                                        <div class="flex items-center mb-3">
                                            <div class="w-10 h-10 rounded-full bg-blockchain-primary/10 flex items-center justify-center mr-3 text-blockchain-primary">
                                                <i class="fas fa-network-wired"></i>
                                            </div>
                                            <div>
                                                <h5 class="text-lg font-medium m-0 text-gray-800">
                                                    <?php echo html(strtok($peer['addr'], ':')) ?>
                                                </h5>
                                                <p class="text-xs text-gray-500 m-0">
                                                    <i class="fas fa-plug mr-1"></i> Connected Peer
                                                </p>
                                            </div>
                                            <div class="ml-auto">
                                                <span class="status-indicator active"></span>
                                            </div>
                                        </div>
                                        
                                        <div class="grid grid-cols-2 gap-4 mt-4">
                                            <div>
                                                <p class="text-gray-500 text-xs mb-1">Connection ID:</p>
                                                <p class="font-mono text-sm mb-0"><?php echo html($peer['id']) ?></p>
                                            </div>
                                            <div>
                                                <p class="text-gray-500 text-xs mb-1">Latency:</p>
                                                <p class="font-mono text-sm mb-0 flex items-center">
                                                    <i class="fas fa-stopwatch mr-1 text-blockchain-primary"></i>
                                                    <?php echo html(number_format($peer['pingtime'], 3)) ?> sec
                                                </p>
                                            </div>
                                        </div>
                                        
                                        <div x-data="{ expanded: false }" class="mt-3 pt-3 border-t border-gray-100">
                                            <div class="flex justify-between items-center">
                                                <button @click="expanded = !expanded" class="px-3 py-1 border border-blockchain-primary text-blockchain-primary text-sm rounded hover:bg-blue-50 transition-colors focus:outline-none flex items-center">
                                                    <span x-text="expanded ? 'Hide Details' : 'View Details'">View Details</span>
                                                    <i class="fas" :class="expanded ? 'fa-chevron-up ml-2' : 'fa-chevron-down ml-2'"></i>
                                                </button>
                                                <span class="text-xs text-gray-500">
                                                    <i class="fas fa-exchange-alt mr-1"></i>
                                                    In: <?php echo html($peer['bytesrecv'] > 1024 ? number_format($peer['bytesrecv']/1024, 1).' KB' : $peer['bytesrecv'].' B') ?> / 
                                                    Out: <?php echo html($peer['bytessent'] > 1024 ? number_format($peer['bytessent']/1024, 1).' KB' : $peer['bytessent'].' B') ?>
                                                </span>
                                            </div>
                                            
                                            <div x-show="expanded" x-transition.duration.200ms class="mt-3">
                                                <div class="p-3 bg-gray-50 rounded border border-gray-200">
                                                    <p class="text-gray-500 text-xs mb-1">Handshake Address:</p>
                                                    <p class="hash-value text-sm mb-3"><?php echo html($peer['handshake']) ?></p>
                                                    
                                                    <p class="text-gray-500 text-xs mb-1">Services:</p>
                                                    <p class="text-sm mb-0">
                                                        <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full mr-1">
                                                            <?php echo html($peer['services']) ?>
                                                        </span>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-10">
                            <div class="w-20 h-20 mx-auto bg-gray-100 rounded-full flex items-center justify-center text-gray-300 mb-4">
                                <i class="fas fa-network-wired text-4xl"></i>
                            </div>
                            <h5 class="text-xl font-medium text-gray-700 mb-2">No Connected Peers</h5>
                            <p class="text-gray-500">This node is not currently connected to any peers on the network.</p>
                            <button class="mt-4 px-4 py-2 bg-blockchain-primary text-white rounded-lg hover:bg-blockchain-dark transition-colors">
                                <i class="fas fa-sync-alt mr-2"></i> Refresh Connection Status
                            </button>
                        </div>
                    <?php endif; ?>
                <?php } ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- My Addresses Section -->
    <div class="bg-white rounded-xl shadow-card overflow-hidden">
        <div class="bg-gradient-primary px-6 py-5 flex justify-between items-center">
            <h3 class="text-white text-lg font-mono font-medium m-0 flex items-center">
                <i class="fas fa-wallet mr-2"></i>My Addresses
            </h3>
            <button type="button" @click="showModal = true" class="bg-white text-blockchain-primary px-4 py-2 rounded-lg text-sm font-medium flex items-center hover:bg-blue-50 transition-colors focus:outline-none shadow-sm hover:shadow-md">
                <i class="fas fa-plus mr-1"></i> New Address
            </button>
        </div>
        <div class="p-6">
            <?php
            if (no_displayed_error_result($getaddresses, multichain('getaddresses', true))) {
                $addressmine = array();

                foreach ($getaddresses as $getaddress)
                    $addressmine[$getaddress['address']] = $getaddress['ismine'];

                $addresspermissions = array();

                if (
                    no_displayed_error_result(
                        $listpermissions,
                        multichain('listpermissions', 'all', implode(',', array_keys($addressmine)))
                    )
                )
                    foreach ($listpermissions as $listpermission)
                        $addresspermissions[$listpermission['address']][$listpermission['type']] = true;

                no_displayed_error_result($getmultibalances, multichain('getmultibalances', array(), array(), 0, true));

                $labels = multichain_labels();

                // Group addresses by whether they hold assets or not
                $addressesWithAssets = [];
                $addressesWithoutAssets = [];

                foreach ($addressmine as $address => $ismine) {
                    $hasAssets = isset($getmultibalances[$address]) && count($getmultibalances[$address]) > 0;
                    
                    if ($hasAssets) {
                        $addressesWithAssets[$address] = $ismine;
                    } else {
                        $addressesWithoutAssets[$address] = $ismine;
                    }
                }

                // Display addresses with assets first
                if (count($addressesWithAssets) > 0 || count($addressesWithoutAssets) > 0):
            ?>
                <div x-data="{ viewAll: false }">
                    <!-- Filter controls -->
                    <div class="flex flex-wrap justify-between items-center mb-6">
                        <div class="flex space-x-2">
                            <button class="px-4 py-2 bg-blockchain-primary text-white rounded-lg shadow-sm">
                                <i class="fas fa-th-large mr-1"></i> All Addresses
                            </button>
                            <button class="px-4 py-2 bg-white border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-50 transition-colors">
                                <i class="fas fa-coins mr-1"></i> With Assets
                            </button>
                            <button class="px-4 py-2 bg-white border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-50 transition-colors">
                                <i class="fas fa-key mr-1"></i> With Permissions
                            </button>
                        </div>
                        <div class="relative mt-2 md:mt-0">
                            <input type="text" placeholder="Search addresses..." class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blockchain-primary/50 focus:border-blockchain-primary">
                            <div class="absolute left-0 top-0 bottom-0 flex items-center pl-3 pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Addresses grid -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <?php
                        $displayedAddresses = 0;
                        $maxDisplayedByDefault = 4;
                        
                        // Process address with assets first
                        foreach ($addressesWithAssets as $address => $ismine):
                            $displayedAddresses++;
                            $isHidden = $displayedAddresses > $maxDisplayedByDefault;
                            
                            if (count(@$addresspermissions[$address]))
                                $permissions = implode(', ', @array_keys($addresspermissions[$address]));
                            else
                                $permissions = 'none';

                            $label = @$labels[$address];
                            $cansetlabel = $ismine && @$addresspermissions[$address]['send'];

                            if ($ismine && !$cansetlabel)
                                $permissions .= ' (cannot set label)';
                            
                            $isNewAddress = ($address == @$getnewaddress);
                            ?>
                            
                            <div x-show="<?php echo $isHidden ? 'viewAll' : 'true'; ?>" 
                                x-transition:enter="transition ease-out duration-300" 
                                x-transition:enter-start="opacity-0 transform scale-95" 
                                x-transition:enter-end="opacity-100 transform scale-100"
                                class="<?php echo $isNewAddress ? 'ring-2 ring-green-500' : ''; ?> bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300 overflow-hidden border border-gray-200">
                                
                                <?php if ($isNewAddress): ?>
                                <div class="bg-green-500 text-white py-2 px-4 flex items-center">
                                    <i class="fas fa-check-circle mr-2"></i> New Address Created Successfully
                                </div>
                                <?php endif; ?>

                                <div class="p-5">
                                    <div class="flex justify-between items-center mb-4">
                                        <h5 class="blockchain-data text-lg font-medium m-0 truncate max-w-[200px] flex items-center">
                                            <?php if (isset($label) && !empty($label)): ?>
                                                <i class="fas fa-tag mr-2 text-blockchain-primary"></i><?php echo html($label); ?>
                                            <?php else: ?>
                                                <i class="fas fa-wallet mr-2 text-blockchain-primary"></i>Address #<?php echo substr($address, 0, 4); ?>...
                                            <?php endif; ?>
                                        </h5>
                                        <div class="flex items-center">
                                            <?php if (!$ismine): ?>
                                                <span class="px-2 py-1 bg-gray-200 text-gray-700 text-xs rounded-full flex items-center">
                                                    <i class="fas fa-eye mr-1"></i> Watch-only
                                                </span>
                                            <?php else: ?>
                                                <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full flex items-center">
                                                    <i class="fas fa-lock mr-1"></i> Owned
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4 bg-gray-50 p-3 rounded-lg">
                                        <label class="block text-gray-500 text-xs mb-1">Address Hash:</label>
                                        <div class="flex">
                                            <input type="text" class="blockchain-data block w-full bg-white border border-gray-200 rounded-l py-2 px-3 text-sm focus:outline-none" 
                                                value="<?php echo html($address); ?>" readonly>
                                            <button type="button" 
                                                class="copy-address bg-gray-100 hover:bg-blockchain-primary hover:text-white transition-colors border border-gray-200 border-l-0 rounded-r px-3 focus:outline-none" 
                                                data-address="<?php echo html($address); ?>"
                                                title="Copy to clipboard">
                                                <i class="far fa-copy"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                        <div>
                                            <label class="block text-gray-500 text-xs mb-1">Permissions:</label>
                                            <div class="flex items-center">
                                                <div class="mr-2 truncate max-w-[160px]">
                                                    <?php if ($permissions !== 'none'): ?>
                                                        <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">
                                                            <?php echo html($permissions); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded">None</span>
                                                    <?php endif; ?>
                                                </div>
                                                <a href="<?php echo chain_page_url_html($chain, 'permissions', array('address' => $address)) ?>" 
                                                    class="inline-flex items-center justify-center w-7 h-7 border border-blockchain-primary text-blockchain-primary rounded-full hover:bg-blue-50 transition-colors">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </div>
                                        </div>
                                        
                                        <?php if ($cansetlabel): ?>
                                        <div>
                                            <label class="block text-gray-500 text-xs mb-1">Label Management:</label>
                                            <a href="<?php echo chain_page_url_html($chain, 'label', array('address' => $address)) ?>" 
                                                class="inline-flex items-center border border-gray-300 text-gray-700 px-3 py-1 rounded hover:bg-gray-50 transition-colors text-sm">
                                                <?php echo isset($label) && !empty($label) ? 'Edit Label' : 'Set Label'; ?>
                                            </a>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if (isset($getmultibalances[$address]) && count($getmultibalances[$address]) > 0): ?>
                                        <div class="bg-gradient-to-r from-blue-50 to-white p-3 rounded-lg border border-blue-100">
                                            <div class="flex justify-between mb-2">
                                                <label class="text-gray-500 text-xs">Asset Balances:</label>
                                                <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 rounded-full">
                                                    <?php echo count($getmultibalances[$address]); ?> Asset<?php echo count($getmultibalances[$address]) > 1 ? 's' : ''; ?>
                                                </span>
                                            </div>
                                            <div class="overflow-hidden">
                                                <div class="overflow-x-auto rounded border border-gray-100">
                                                    <table class="min-w-full bg-white text-sm">
                                                        <thead class="bg-gray-50">
                                                            <tr>
                                                                <th class="py-2 px-3 text-left border-b border-gray-200 font-medium">Asset</th>
                                                                <th class="py-2 px-3 text-right border-b border-gray-200 font-medium">Balance</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($getmultibalances[$address] as $addressbalance): ?>
                                                            <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                                                <td class="py-2 px-3 font-medium">
                                                                    <div class="flex items-center">
                                                                        <div class="w-6 h-6 rounded-full bg-blockchain-primary/10 flex items-center justify-center mr-2 text-blockchain-primary">
                                                                            <i class="fas fa-coins text-xs"></i>
                                                                        </div>
                                                                        <?php echo html($addressbalance['name']); ?>
                                                                    </div>
                                                                </td>
                                                                <td class="py-2 px-3 text-right blockchain-data font-medium">
                                                                    <?php echo html($addressbalance['qty']); ?>
                                                                </td>
                                                            </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            <div class="text-right mt-2">
                                                <a href="./?chain=<?php echo html($chain) ?>&page=send" class="inline-flex items-center text-xs text-blockchain-primary hover:text-blockchain-dark">
                                                    <i class="fas fa-paper-plane mr-1"></i> Send Assets
                                                </a>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="bg-gray-50 text-center py-6 rounded-lg border border-gray-100">
                                            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 text-gray-400 mb-3">
                                                <i class="fas fa-coins"></i>
                                            </div>
                                            <p class="text-gray-500 text-sm mb-2">No assets held at this address</p>
                                            <a href="./?chain=<?php echo html($chain) ?>&page=issue" class="inline-flex items-center text-xs text-blockchain-primary hover:text-blockchain-dark">
                                                <i class="fas fa-plus-circle mr-1"></i> Issue Asset
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="bg-gray-50 px-5 py-3 border-t border-gray-200 flex justify-between items-center">
                                    <span class="text-xs text-gray-500">
                                        <i class="fas fa-history mr-1"></i> Balance last updated: <?php echo date('H:i:s'); ?>
                                    </span>
                                    <div>
                                        <a href="./?chain=<?php echo html($chain) ?>&page=permissions&address=<?php echo html($address); ?>" 
                                           class="text-xs text-gray-700 hover:text-blockchain-primary mr-3 inline-flex items-center">
                                            <i class="fas fa-key mr-1"></i> Manage
                                        </a>
                                        <a href="#" class="text-xs text-gray-700 hover:text-blockchain-primary inline-flex items-center">
                                            <i class="fas fa-external-link-alt mr-1"></i> Explorer
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <!-- Process addresses without assets -->
                        <?php foreach ($addressesWithoutAssets as $address => $ismine):
                            $displayedAddresses++;
                            $isHidden = $displayedAddresses > $maxDisplayedByDefault;
                            
                            if (count(@$addresspermissions[$address]))
                                $permissions = implode(', ', @array_keys($addresspermissions[$address]));
                            else
                                $permissions = 'none';

                            $label = @$labels[$address];
                            $cansetlabel = $ismine && @$addresspermissions[$address]['send'];

                            if ($ismine && !$cansetlabel)
                                $permissions .= ' (cannot set label)';
                            
                            $isNewAddress = ($address == @$getnewaddress);
                        ?>
                            <div x-show="<?php echo $isHidden ? 'viewAll' : 'true'; ?>"
                                x-transition:enter="transition ease-out duration-300" 
                                x-transition:enter-start="opacity-0 transform scale-95" 
                                x-transition:enter-end="opacity-100 transform scale-100"
                                class="<?php echo $isNewAddress ? 'ring-2 ring-green-500' : ''; ?> bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300 overflow-hidden border border-gray-200">
                                
                                <?php if ($isNewAddress): ?>
                                <div class="bg-green-500 text-white py-2 px-4 flex items-center">
                                    <i class="fas fa-check-circle mr-2"></i> New Address Created Successfully
                                </div>
                                <?php endif; ?>
                                
                                <div class="p-5">
                                    <div class="flex justify-between items-center mb-4">
                                        <h5 class="blockchain-data text-lg font-medium m-0 truncate max-w-[200px] flex items-center">
                                            <?php if (isset($label) && !empty($label)): ?>
                                                <i class="fas fa-tag mr-2 text-blockchain-primary"></i><?php echo html($label); ?>
                                            <?php else: ?>
                                                <i class="fas fa-wallet mr-2 text-gray-400"></i>Address #<?php echo substr($address, 0, 4); ?>...
                                            <?php endif; ?>
                                        </h5>
                                        <div class="flex items-center">
                                            <?php if (!$ismine): ?>
                                                <span class="px-2 py-1 bg-gray-200 text-gray-700 text-xs rounded-full flex items-center">
                                                    <i class="fas fa-eye mr-1"></i> Watch-only
                                                </span>
                                            <?php else: ?>
                                                <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full flex items-center">
                                                    <i class="fas fa-lock mr-1"></i> Owned
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4 bg-gray-50 p-3 rounded-lg">
                                        <label class="block text-gray-500 text-xs mb-1">Address Hash:</label>
                                        <div class="flex">
                                            <input type="text" class="blockchain-data block w-full bg-white border border-gray-200 rounded-l py-2 px-3 text-sm focus:outline-none" 
                                                value="<?php echo html($address); ?>" readonly>
                                            <button type="button" 
                                                class="copy-address bg-gray-100 hover:bg-blockchain-primary hover:text-white transition-colors border border-gray-200 border-l-0 rounded-r px-3 focus:outline-none" 
                                                data-address="<?php echo html($address); ?>"
                                                title="Copy to clipboard">
                                                <i class="far fa-copy"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                        <div>
                                            <label class="block text-gray-500 text-xs mb-1">Permissions:</label>
                                            <div class="flex items-center">
                                                <div class="mr-2 truncate max-w-[160px]">
                                                    <?php if ($permissions !== 'none'): ?>
                                                        <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">
                                                            <?php echo html($permissions); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded">None</span>
                                                    <?php endif; ?>
                                                </div>
                                                <a href="<?php echo chain_page_url_html($chain, 'permissions', array('address' => $address)) ?>" 
                                                    class="inline-flex items-center justify-center w-7 h-7 border border-blockchain-primary text-blockchain-primary rounded-full hover:bg-blue-50 transition-colors">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </div>
                                        </div>
                                        
                                        <?php if ($cansetlabel): ?>
                                        <div>
                                            <label class="block text-gray-500 text-xs mb-1">Label Management:</label>
                                            <a href="<?php echo chain_page_url_html($chain, 'label', array('address' => $address)) ?>" 
                                                class="inline-flex items-center border border-gray-300 text-gray-700 px-3 py-1 rounded hover:bg-gray-50 transition-colors text-sm">
                                                <?php echo isset($label) && !empty($label) ? 'Edit Label' : 'Set Label'; ?>
                                            </a>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Empty balance notice -->
                                    <div class="bg-gray-50 text-center py-6 rounded-lg border border-gray-100">
                                        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 text-gray-400 mb-3">
                                            <i class="fas fa-coins"></i>
                                        </div>
                                        <p class="text-gray-500 text-sm mb-2">No assets held at this address</p>
                                        <a href="./?chain=<?php echo html($chain) ?>&page=issue" class="inline-flex items-center text-xs text-blockchain-primary hover:text-blockchain-dark">
                                            <i class="fas fa-plus-circle mr-1"></i> Issue Asset
                                        </a>
                                    </div>
                                </div>
                                <div class="bg-gray-50 px-5 py-3 border-t border-gray-200 flex justify-between items-center">
                                    <span class="text-xs text-gray-500">
                                        <i class="fas fa-clock mr-1"></i> Created: <?php echo date('d M Y'); ?>
                                    </span>
                                    <div>
                                        <a href="./?chain=<?php echo html($chain) ?>&page=permissions&address=<?php echo html($address); ?>" 
                                           class="text-xs text-gray-700 hover:text-blockchain-primary mr-3 inline-flex items-center">
                                            <i class="fas fa-key mr-1"></i> Manage
                                        </a>
                                        <a href="#" class="text-xs text-gray-700 hover:text-blockchain-primary inline-flex items-center">
                                            <i class="fas fa-external-link-alt mr-1"></i> Explorer
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if ($displayedAddresses > $maxDisplayedByDefault): ?>
                        <div class="flex justify-center mt-6">
                            <button @click="viewAll = !viewAll" 
                                    class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 transition-colors rounded-lg px-5 py-2.5 flex items-center shadow-sm">
                                <i class="fas" :class="viewAll ? 'fa-chevron-up mr-2' : 'fa-chevron-down mr-2'"></i>
                                <span x-text="viewAll ? 'Show Less Addresses' : 'Show All Addresses (<?php echo $displayedAddresses; ?>)'"></span>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- No addresses state -->
                <div class="text-center py-12">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gray-100 text-gray-400 mb-4">
                        <i class="fas fa-wallet text-3xl"></i>
                    </div>
                    <h4 class="text-xl font-medium text-gray-700 mb-2">No Addresses Available</h4>
                    <p class="text-gray-500 mb-6">You don't have any addresses in this blockchain yet.</p>
                    <button type="button" @click="showModal = true" 
                        class="px-5 py-2.5 bg-gradient-button text-white rounded-lg hover:shadow-md transition-shadow flex items-center mx-auto">
                        <i class="fas fa-plus-circle mr-2"></i> Create Your First Address
                    </button>
                </div>
            <?php endif; ?>
            <?php } ?>
        </div>
    </div>

    <!-- Enhanced Modal for New Address -->
    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm z-40 flex items-center justify-center" 
         x-show="showModal" 
         x-cloak
         x-transition:enter="transition ease-out duration-300" 
         x-transition:enter-start="opacity-0" 
         x-transition:enter-end="opacity-100" 
         x-transition:leave="transition ease-in duration-200" 
         x-transition:leave-start="opacity-100" 
         x-transition:leave-end="opacity-0">
        
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 overflow-hidden" 
             @click.outside="showModal = false"
             x-show="showModal" 
             x-cloak
             x-transition:enter="transition ease-out duration-300" 
             x-transition:enter-start="opacity-0 transform translate-y-4" 
             x-transition:enter-end="opacity-100 transform translate-y-0" 
             x-transition:leave="transition ease-in duration-200" 
             x-transition:leave-start="opacity-100 transform translate-y-0" 
             x-transition:leave-end="opacity-0 transform translate-y-4">
            
            <div class="bg-gradient-primary px-6 py-5 flex justify-between items-center">
                <h3 class="text-white text-lg font-medium flex items-center">
                    <i class="fas fa-plus-circle mr-2"></i>Create New Address
                </h3>
                <button type="button" @click="showModal = false" 
                        class="text-white hover:text-gray-200 focus:outline-none w-8 h-8 flex items-center justify-center rounded-full hover:bg-white/10 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="p-6">
                <div class="mb-6 bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                    <div class="flex">
                        <div class="flex-shrink-0 text-blue-500">
                            <i class="fas fa-info-circle text-lg"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">About Blockchain Addresses</h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <p>Addresses are unique identifiers that serve as accounts in the blockchain. Each address:</p>
                                <ul class="list-disc list-inside mt-1 space-y-1">
                                    <li>Can hold assets and permissions</li>
                                    <li>Requires permissions to perform operations</li>
                                    <li>Can be labeled for easier identification</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <form method="post" action="<?php echo chain_page_url_html($chain) ?>" class="space-y-6">
                    <div x-data="{ advanced: false }">
                        <div class="mb-3 flex justify-between items-center">
                            <h4 class="font-medium text-gray-800 m-0">Address Settings</h4>
                            <button type="button" @click="advanced = !advanced" 
                                    class="text-xs text-blockchain-primary flex items-center focus:outline-none">
                                <span x-text="advanced ? 'Hide Advanced' : 'Advanced Options'"></span>
                                <i class="fas fa-chevron-down ml-1" x-bind:class="advanced ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                            </button>
                        </div>
                        
                        <div x-show="advanced" x-transition class="mb-5 bg-gray-50 p-4 rounded border border-gray-200">
                            <div class="text-sm text-gray-500 mb-3">
                                <p>These settings are optional and can be configured later.</p>
                            </div>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Initial Label
                                    </label>
                                    <input type="text" name="label" placeholder="E.g. Main Wallet" 
                                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blockchain-primary focus:border-blockchain-primary">
                                    <p class="mt-1 text-xs text-gray-500">A friendly name to identify this address</p>
                                </div>
                                
                                <div x-data="{ checked: false }">
                                    <div class="flex items-center">
                                        <input id="grant-permissions" type="checkbox" x-model="checked"
                                               class="w-4 h-4 text-blockchain-primary focus:ring-blockchain-primary border-gray-300 rounded">
                                        <label for="grant-permissions" class="ml-2 block text-sm text-gray-700">
                                            Grant Initial Permissions
                                        </label>
                                    </div>
                                    
                                    <div x-show="checked" x-transition class="mt-3 ml-6">
                                        <div class="space-y-2">
                                            <div class="flex items-center">
                                                <input id="perm-send" type="checkbox" name="permissions[]" value="send"
                                                       class="w-4 h-4 text-blockchain-primary focus:ring-blockchain-primary border-gray-300 rounded">
                                                <label for="perm-send" class="ml-2 block text-sm text-gray-700">
                                                    Send Assets
                                                </label>
                                            </div>
                                            <div class="flex items-center">
                                                <input id="perm-receive" type="checkbox" name="permissions[]" value="receive" checked
                                                       class="w-4 h-4 text-blockchain-primary focus:ring-blockchain-primary border-gray-300 rounded">
                                                <label for="perm-receive" class="ml-2 block text-sm text-gray-700">
                                                    Receive Assets
                                                </label>
                                            </div>
                                            <div class="flex items-center">
                                                <input id="perm-issue" type="checkbox" name="permissions[]" value="issue"
                                                       class="w-4 h-4 text-blockchain-primary focus:ring-blockchain-primary border-gray-300 rounded">
                                                <label for="perm-issue" class="ml-2 block text-sm text-gray-700">
                                                    Issue Assets
                                                </label>
                                            </div>
                                        </div>
                                        <p class="mt-2 text-xs text-gray-500">Permissions can be managed later in the Permissions page</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>