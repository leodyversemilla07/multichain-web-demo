<?php

$const_permission_names = array(
	'connect' => 'Connect',
	'send' => 'Send',
	'receive' => 'Receive',
	'create' => 'Create',
	'issue' => 'Issue',
	'mine' => 'Mine',
	'activate' => 'Activate',
	'admin' => 'Admin',
);

if (multichain_has_custom_permissions())
	$const_permission_names = array_merge($const_permission_names, array(
		'high1' => 'High 1',
		'high2' => 'High 2',
		'high3' => 'High 3',
		'low1' => 'Low 1',
		'low2' => 'Low 2',
		'low3' => 'Low 3',
	));

if (@$_POST['grantrevoke']) {
	$permissions = array();

	foreach ($const_permission_names as $type => $label)
		if (@$_POST[$type])
			$permissions[] = $type;

	if ($_POST['operation'] == 'grant')
		$success = no_displayed_error_result($permissiontxid, multichain(
			'grantfrom',
			$_POST['from'],
			$_POST['to'],
			implode(',', $permissions)
		));
	elseif ($_POST['operation'] == 'revoke')
		$success = no_displayed_error_result($permissiontxid, multichain(
			'revokefrom',
			$_POST['from'],
			$_POST['to'],
			implode(',', $permissions)
		));

	if ($success)
		output_success_text('Permissions successfully changed in transaction ' . $permissiontxid);

	$to = $_POST['to'];

} else
	$to = @$_GET['address'];

$adminaddresses = array();
$keymyaddresses = array();
$getinfo = multichain_getinfo();
$labels = array();

if (no_displayed_error_result($getaddresses, multichain('getaddresses', true))) {

	if (
		no_displayed_error_result(
			$listpermissions,
			multichain('listpermissions', 'admin,activate', implode(',', array_get_column($getaddresses, 'address')))
		)
	)
		$adminaddresses = array_unique(array_get_column($listpermissions, 'address'));

	$labels = multichain_labels();

	foreach ($getaddresses as $address)
		if ($address['ismine'])
			$keymyaddresses[$address['address']] = true;
}
?>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
    <!-- Current Permissions Panel -->
    <div class="lg:col-span-5">
        <div class="bg-white rounded-xl shadow-card border border-gray-100 overflow-hidden">
            <!-- Panel Header -->
            <div class="bg-gradient-primary p-5 relative">
                <h3 class="text-xl font-medium text-white m-0 flex items-center">
                    <i class="fas fa-key mr-3"></i> Current Permissions
                </h3>
                <!-- Decorative background pattern -->
                <div class="absolute inset-0 opacity-10" 
                    style="background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCA2MCA2MCIgc3R5bGU9Im9wYWNpdHk6MC4xNTtmaWxsOiNmZmZmZmYiPjxwYXRoIGQ9Ik0zMCwxMCwxNSwyMFYzMEwxNSw0MEwzMCw1MEw0NSw0MEw0NSwzMEw0NSwyMEwzMCwxMFoiPjwvcGF0aD48L3N2Zz4=');">
                </div>
            </div>
            
            <!-- Permissions List -->
            <div class="p-5 space-y-4 max-h-[50vh] overflow-y-auto">
                <?php
                if (no_displayed_error_result($listpermissions, multichain('listpermissions'))) {
                    $addresspermissions = array();

                    foreach ($keymyaddresses as $address => $dummy)
                        $addresspermissions[$address] = array(); // ensure all local addresses shown as well
                
                    foreach ($listpermissions as $permission)
                        $addresspermissions[$permission['address']][$permission['type']] = true;
                    
                    if (empty($addresspermissions)) {
                        echo '<div class="p-8 text-center text-gray-500">';
                        echo '<div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-50 flex items-center justify-center">';
                        echo '<i class="fas fa-user-slash text-gray-400 text-xl"></i>';
                        echo '</div>';
                        echo '<p>No addresses with permissions found.</p>';
                        echo '</div>';
                    }

                    foreach ($addresspermissions as $address => $permissions) {
                        if ($address == $getinfo['burnaddress'])
                            continue;

                        if (count($permissions))
                            $permissions_text = implode(', ', array_keys($permissions));
                        else
                            $permissions_text = 'none';

                        $label = @$labels[$address];
                        ?>
                        <div class="bg-white rounded-lg border <?php echo ($address == @$_POST['to']) ? 'border-green-400 shadow-lg ring-2 ring-green-100' : 'border-gray-200' ?>">
                            <div class="divide-y divide-gray-100">
                                <?php
                                if (isset($label)) {
                                    ?>
                                    <div class="flex p-3">
                                        <div class="w-1/4 text-gray-500 font-medium">Label</div>
                                        <div class="w-3/4 text-gray-800"><?php echo html($label) ?></div>
                                    </div>
                                    <?php
                                }
                                ?>
                                <div class="flex p-3">
                                    <div class="w-1/4 text-gray-500 font-medium">Address</div>
                                    <div class="w-3/4 break-all">
                                        <span class="hash-value w-full">
                                            <?php echo html($address) ?>
                                        </span>
                                        <?php if (@$keymyaddresses[$address]) { ?>
                                            <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                <i class="fas fa-user-check mr-1"></i> Local
                                            </span>
                                        <?php } ?>
                                    </div>
                                </div>
                                <div class="flex p-3">
                                    <div class="w-1/4 text-gray-500 font-medium">Permissions</div>
                                    <div class="w-3/4">
                                        <?php if ($permissions_text === 'none') { ?>
                                            <span class="inline-block px-2 py-1 text-xs text-gray-500 bg-gray-100 rounded">None</span>
                                        <?php } else { 
                                            $permArray = explode(', ', $permissions_text);
                                            echo '<div class="flex flex-wrap gap-1">';
                                            foreach ($permArray as $perm) {
                                                $colorClass = '';
                                                // Assign different colors based on permission type
                                                switch ($perm) {
                                                    case 'admin':
                                                        $colorClass = 'bg-purple-100 text-purple-800';
                                                        $icon = 'fas fa-crown';
                                                        break;
                                                    case 'mine':
                                                        $colorClass = 'bg-yellow-100 text-yellow-800';
                                                        $icon = 'fas fa-hammer';
                                                        break;
                                                    case 'issue':
                                                        $colorClass = 'bg-green-100 text-green-800';
                                                        $icon = 'fas fa-coins';
                                                        break;
                                                    case 'connect':
                                                        $colorClass = 'bg-blue-100 text-blue-800';
                                                        $icon = 'fas fa-plug';
                                                        break;
                                                    default:
                                                        $colorClass = 'bg-gray-100 text-gray-800';
                                                        $icon = 'fas fa-check';
                                                }
                                                echo '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium '.$colorClass.'">';
                                                echo '<i class="'.$icon.' mr-1"></i> '.ucfirst($perm);
                                                echo '</span>';
                                            }
                                            echo '</div>';
                                        } ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Change Permissions Form Panel -->
    <div class="lg:col-span-7">
        <div class="bg-white rounded-xl shadow-card border border-gray-100 overflow-hidden">
            <!-- Panel Header -->
            <div class="bg-gradient-primary p-5 relative">
                <h3 class="text-xl font-medium text-white m-0 flex items-center">
                    <i class="fas fa-edit mr-3"></i> Change Permissions
                </h3>
                <!-- Decorative background pattern -->
                <div class="absolute inset-0 opacity-10" 
                    style="background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCA2MCA2MCIgc3R5bGU9Im9wYWNpdHk6MC4xNTtmaWxsOiNmZmZmZmYiPjxwYXRoIGQ9Ik0zMCwxMCwxNSwyMFYzMEwxNSw0MEwzMCw1MEw0NSw0MEw0NSwzMEw0NSwyMEwzMCwxMFoiPjwvcGF0aD48L3N2Zz4=');">
                </div>
            </div>
            
            <!-- Permissions Form -->
            <div class="p-6">
                <form method="post" action="./?chain=<?php echo html($_GET['chain']) ?>&page=<?php echo html($_GET['page']) ?>">
                    <!-- Admin Address Field -->
                    <div class="mb-6">
                        <label for="from" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-user-shield text-blockchain-primary mr-2"></i> Admin Address:
                        </label>
                        <div class="relative">
                            <select name="from" id="from" class="w-full pl-4 pr-10 py-2 border-gray-300 focus:ring-blockchain-primary focus:border-blockchain-primary rounded-lg shadow-sm appearance-none">
                                <?php
                                if (empty($adminaddresses)) {
                                    echo '<option value="">No admin addresses available</option>';
                                } else {
                                    foreach ($adminaddresses as $address) {
                                        ?>
                                        <option value="<?php echo html($address) ?>">
                                            <?php echo format_address_html($address, true, $labels) ?>
                                        </option>
                                        <?php
                                    }
                                }
                                ?>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                                <i class="fas fa-chevron-down"></i>
                            </div>
                        </div>
                        <?php if (empty($adminaddresses)) { ?>
                            <p class="mt-2 text-sm text-yellow-600">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                No admin addresses found. You need admin permissions to make changes.
                            </p>
                        <?php } ?>
                    </div>

                    <!-- Target Address Field -->
                    <div class="mb-6">
                        <label for="to" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-user text-blockchain-primary mr-2"></i> For Address:
                        </label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm"><i class="fas fa-address-card"></i></span>
                            </div>
                            <input type="text" name="to" id="to" 
                                class="focus:ring-blockchain-primary focus:border-blockchain-primary block w-full pl-10 pr-3 py-2 border-gray-300 rounded-md font-mono" 
                                placeholder="Enter blockchain address..." value="<?php echo html($to) ?>">
                        </div>
                    </div>

                    <!-- Operation Field -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                            <i class="fas fa-exchange-alt text-blockchain-primary mr-2"></i> Operation:
                        </label>
                        <div class="flex space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="operation" value="grant" checked
                                    class="focus:ring-blockchain-primary h-4 w-4 text-blockchain-primary border-gray-300">
                                <span class="ml-2 text-gray-700">
                                    <i class="fas fa-plus-circle text-green-500 mr-1"></i> Grant
                                </span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="operation" value="revoke"
                                    class="focus:ring-blockchain-primary h-4 w-4 text-blockchain-primary border-gray-300">
                                <span class="ml-2 text-gray-700">
                                    <i class="fas fa-minus-circle text-red-500 mr-1"></i> Revoke
                                </span>
                            </label>
                        </div>
                    </div>

                    <!-- Permissions Checkboxes -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                            <i class="fas fa-key text-blockchain-primary mr-2"></i> Permissions:
                        </label>
                        
                        <!-- Group permissions in cards with visual organization -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <!-- Basic Permissions Card -->
                            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                <h4 class="text-sm font-medium text-gray-700 mb-3 flex items-center">
                                    <i class="fas fa-shield-alt text-blockchain-primary mr-2"></i> Basic Permissions
                                </h4>
                                <div class="space-y-3">
                                    <?php
                                    $basicPermissions = ['connect', 'send', 'receive'];
                                    foreach ($basicPermissions as $type) {
                                        $label = $const_permission_names[$type];
                                        ?>
                                        <label class="inline-flex items-center mr-4">
                                            <input type="checkbox" name="<?php echo html($type) ?>" value="1" 
                                                class="focus:ring-blockchain-primary h-4 w-4 text-blockchain-primary border-gray-300 rounded">
                                            <span class="ml-2 text-gray-700"><?php echo html($label) ?></span>
                                        </label>
                                        <?php
                                    }
                                    ?>
                                </div>
                            </div>
                            
                            <!-- Advanced Permissions Card -->
                            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                <h4 class="text-sm font-medium text-gray-700 mb-3 flex items-center">
                                    <i class="fas fa-cog text-blockchain-primary mr-2"></i> Advanced Permissions
                                </h4>
                                <div class="space-y-3">
                                    <?php
                                    $advancedPermissions = ['create', 'issue', 'mine', 'activate', 'admin'];
                                    foreach ($advancedPermissions as $type) {
                                        $label = $const_permission_names[$type];
                                        $specialClass = ($type === 'admin') ? 'border border-purple-300 bg-purple-50 rounded px-2' : '';
                                        ?>
                                        <label class="inline-flex items-center mr-4">
                                            <input type="checkbox" name="<?php echo html($type) ?>" value="1" 
                                                class="focus:ring-blockchain-primary h-4 w-4 text-blockchain-primary border-gray-300 rounded">
                                            <span class="ml-2 text-gray-700 <?php echo $specialClass ?>"><?php echo html($label) ?></span>
                                        </label>
                                        <?php
                                    }
                                    ?>
                                </div>
                            </div>
                            
                            <?php if (multichain_has_custom_permissions()) { ?>
                                <!-- Custom High Permissions Card -->
                                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                    <h4 class="text-sm font-medium text-gray-700 mb-3 flex items-center">
                                        <i class="fas fa-level-up-alt text-blockchain-primary mr-2"></i> High Priority Custom
                                    </h4>
                                    <div class="space-y-3">
                                        <?php
                                        $highCustom = ['high1', 'high2', 'high3'];
                                        foreach ($highCustom as $type) {
                                            $label = $const_permission_names[$type];
                                            ?>
                                            <label class="inline-flex items-center mr-4">
                                                <input type="checkbox" name="<?php echo html($type) ?>" value="1" 
                                                    class="focus:ring-blockchain-primary h-4 w-4 text-blockchain-primary border-gray-300 rounded">
                                                <span class="ml-2 text-gray-700"><?php echo html($label) ?></span>
                                            </label>
                                            <?php
                                        }
                                        ?>
                                    </div>
                                </div>
                                
                                <!-- Custom Low Permissions Card -->
                                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                    <h4 class="text-sm font-medium text-gray-700 mb-3 flex items-center">
                                        <i class="fas fa-level-down-alt text-blockchain-primary mr-2"></i> Low Priority Custom
                                    </h4>
                                    <div class="space-y-3">
                                        <?php
                                        $lowCustom = ['low1', 'low2', 'low3'];
                                        foreach ($lowCustom as $type) {
                                            $label = $const_permission_names[$type];
                                            ?>
                                            <label class="inline-flex items-center mr-4">
                                                <input type="checkbox" name="<?php echo html($type) ?>" value="1" 
                                                    class="focus:ring-blockchain-primary h-4 w-4 text-blockchain-primary border-gray-300 rounded">
                                                <span class="ml-2 text-gray-700"><?php echo html($label) ?></span>
                                            </label>
                                            <?php
                                        }
                                        ?>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                    
                    <!-- Submit Button Section -->
                    <div class="mt-8 flex items-center">
                        <button type="submit" name="grantrevoke" value="1" class="relative inline-flex items-center px-6 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-gradient-button shadow-sm hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blockchain-primary transition-all duration-200">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <i class="fas fa-key mr-2"></i> Change Permissions
                        </button>
                        
                        <a href="./?chain=<?php echo html($_GET['chain']) ?>&page=<?php echo html($_GET['page']) ?>" class="ml-4 inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blockchain-primary">
                            <i class="fas fa-redo-alt mr-2"></i> Reset
                        </a>
                    </div>
                </form>
                
                <!-- Infobox for permission management -->
                <div class="mt-8 bg-blue-50 rounded-lg p-4 border border-blue-100">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-info-circle text-blue-400 text-lg"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">About Permissions</h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <p>Permissions control what operations addresses can perform on the blockchain:</p>
                                <ul class="list-disc pl-5 mt-1 space-y-1">
                                    <li><strong>Admin:</strong> Can manage permissions for other addresses</li>
                                    <li><strong>Mine:</strong> Can create blocks</li>
                                    <li><strong>Issue:</strong> Can issue new assets</li>
                                    <li><strong>Connect:</strong> Can connect to the blockchain</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>