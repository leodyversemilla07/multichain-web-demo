<?php
// Initialize toast variables
$toast_message = '';
$toast_type = 'success';

// Process form submission
if (@$_POST['setlabel']) {
    $address = $_POST['address'];
    
    try {
        if (empty($address)) {
            throw new Exception("Address cannot be empty");
        }
        
        // Attempt to update the label
        $result = multichain(
            'publishfrom',
            $address,
            'root',
            '',
            bin2hex($_POST['label'])
        );
        
        if ($result) {
            $labeltxid = $result;
            $toast_message = 'Label successfully updated in transaction ' . $labeltxid;
            $toast_type = 'success';
            
            // Legacy success message
            output_success_text('Label successfully updated in transaction ' . $labeltxid);
        } else {
            $toast_type = 'error';
            $toast_message = 'Failed to update label. Please check your permissions.';
        }
    } catch (Exception $e) {
        $toast_type = 'error';
        $toast_message = 'Error: ' . $e->getMessage();
        
        // Also output using the legacy error method
        output_error_text($e->getMessage());
    }
    
    // Store in session in case page reloads
    $_SESSION['label_toast_message'] = $toast_message;
    $_SESSION['label_toast_type'] = $toast_type;
} else {
    // Check if we're coming back after a redirect with a session message
    if (isset($_SESSION['label_toast_message'])) {
        $toast_message = $_SESSION['label_toast_message'];
        $toast_type = $_SESSION['label_toast_type'];
        
        // Clear the session variables after use
        unset($_SESSION['label_toast_message']);
        unset($_SESSION['label_toast_type']);
    }
    
    $address = isset($_GET['address']) ? $_GET['address'] : '';
}

$labels = multichain_labels();

// Force display regardless of session for debugging
$forceToast = !empty($toast_message);
?>

<!-- Toast Container - positioned at the top for visibility -->
<div id="toast-container" class="fixed top-5 right-5 z-50"></div>

<!-- Hidden diagnostic field for debugging -->
<div id="toast-debug" class="hidden">
    <span id="toast-message-debug"><?php echo htmlspecialchars($toast_message); ?></span>
    <span id="toast-type-debug"><?php echo htmlspecialchars($toast_type); ?></span>
</div>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-wrap items-center justify-between gap-4">
        <h2 class="text-2xl font-mono font-medium m-0 flex items-center">
            <i class="fas fa-tag text-blockchain-primary mr-3"></i> Set Address Label
        </h2>
        <a href="<?php echo chain_page_url_html($chain) ?>"
            class="text-gray-600 hover:text-blockchain-primary transition-colors flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
        </a>
    </div>

    <!-- Instruction Card -->
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-md shadow-sm">
        <div class="flex">
            <div class="flex-shrink-0 text-blue-500">
                <i class="fas fa-info-circle text-lg"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">About Address Labels</h3>
                <div class="mt-1 text-sm text-blue-700">
                    <p>Labels make it easier to identify blockchain addresses. They are:</p>
                    <ul class="list-disc list-inside mt-1 space-y-1">
                        <li>Stored on the blockchain as part of the metadata</li>
                        <li>Visible to other participants in this blockchain</li>
                        <li>Associated with the specific address permanently</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Card -->
    <div class="bg-white rounded-xl shadow-card overflow-hidden">
        <div class="bg-gradient-primary px-6 py-5">
            <h3 class="text-white text-lg font-mono font-medium m-0 flex items-center">
                <i class="fas fa-pen-alt mr-2"></i> Label Management
            </h3>
        </div>

        <div class="p-6">
            <!-- Form Card -->
            <form method="post"
                action="./?chain=<?php echo html($_GET['chain']) ?>&page=<?php echo html($_GET['page']) ?>"
                class="space-y-6">
                <!-- Address Field -->
                <div class="space-y-2">
                    <label for="address" class="flex items-center text-gray-700 font-medium">
                        <i class="fas fa-wallet text-blockchain-primary mr-2"></i> Address
                    </label>
                    <div class="mt-1">
                        <div class="relative rounded-md shadow-sm">
                            <input type="text" name="address" id="address" value="<?php echo html($address) ?>"
                                class="blockchain-data focus:ring-blockchain-primary focus:border-blockchain-primary block w-full py-3 px-4 sm:text-sm border border-gray-300 rounded-md bg-gray-50"
                                <?php echo @$_GET['address'] ? 'readonly' : '' ?>>
                            <?php if (@$_GET['address']): ?>
                                <div
                                    class="absolute inset-y-0 right-0 flex items-center px-3 bg-gray-100 rounded-r-md border-l border-gray-300">
                                    <span class="text-gray-500 sm:text-sm">
                                        <i class="fas fa-lock text-gray-400"></i>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">The blockchain address you want to label</p>
                    </div>
                </div>

                <!-- Label Field - Fixed structure -->
                <div class="space-y-2">
                    <label for="label" class="flex items-center text-gray-700 font-medium">
                        <i class="fas fa-tag text-blockchain-primary mr-2"></i> Label
                    </label>
                    <div class="mt-1">
                        <div class="relative rounded-md shadow-sm">
                            <input type="text" name="label" id="label"
                                value="<?php echo isset($labels[$address]) ? html($labels[$address]) : '' ?>"
                                placeholder="Enter a descriptive label for this address"
                                class="focus:ring-blockchain-primary focus:border-blockchain-primary block w-full py-3 px-4 sm:text-sm border border-gray-300 rounded-md">
                            <?php if (isset($labels[$address]) && !empty($labels[$address])): ?>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                    <span class="text-gray-500 sm:text-sm">
                                        <i class="fas fa-check-circle text-green-500"></i>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">
                            Choose a memorable name that helps you identify this address
                        </p>
                    </div>
                </div>

                <!-- Preview Card -->
                <?php if (!empty($address)): ?>
                    <div class="mt-6 bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <h4 class="text-sm font-medium text-gray-700 mb-3">Preview:</h4>
                        <div x-data="{}">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div
                                        class="w-10 h-10 rounded-full bg-blockchain-primary/10 flex items-center justify-center text-blockchain-primary mr-3">
                                        <i class="fas fa-tag"></i>
                                    </div>
                                    <div>
                                        <p class="font-mono text-sm font-medium text-gray-900 m-0"
                                            x-text="document.getElementById('label').value || 'Unnamed Address'"></p>
                                        <p class="text-xs text-gray-500 m-0"><?php echo substr(html($address), 0, 16) ?>...
                                        </p>
                                    </div>
                                </div>
                                <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full">
                                    <i class="fas fa-check-circle mr-1"></i> Labeled
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Submit Button -->
                <div class="flex justify-end pt-4">
                    <button type="button" onclick="window.location='<?php echo chain_page_url_html($chain) ?>'"
                        class="mr-3 px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                        Cancel
                    </button>
                    <button type="submit" name="setlabel"
                        class="inline-flex items-center px-4 py-2 bg-gradient-button text-white rounded-md shadow-sm text-sm font-medium hover:shadow-md transition-shadow focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blockchain-primary">
                        <i class="fas fa-save mr-2"></i> Save Label
                    </button>
                </div>
            </form>

            <!-- Transaction History (if label has been set) -->
            <?php if (isset($labels[$address]) && !empty($labels[$address])): ?>
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <h4 class="text-sm font-medium text-gray-700 mb-3 flex items-center">
                        <i class="fas fa-history text-blockchain-primary mr-2"></i> Label History
                    </h4>
                    <div class="bg-gray-50 rounded-md p-3 text-sm text-gray-600">
                        <p class="m-0">
                            Labels are stored as transactions on the blockchain. Changes are permanent and auditable.
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Debug button - Properly positioned and structured -->
<div class="fixed bottom-5 left-5 z-50">
    <button type="button" onclick="testToasts()" 
            class="px-3 py-1 bg-gray-200 border border-gray-300 rounded text-xs">
        Test Toast
    </button>
</div>

<!-- Enhanced toast functionality -->
<script>
    // Improved toast function
    function displayToast(message, type = 'success') {
        console.log('Displaying toast:', message, type);
        
        // Ensure the container exists
        let toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            console.log('Creating toast container');
            toastContainer = document.createElement('div');
            toastContainer.id = 'toast-container';
            toastContainer.className = 'fixed top-5 right-5 z-50';
            document.body.appendChild(toastContainer);
        }
        
        // Create toast
        const toast = document.createElement('div');
        
        // Set appearance based on type
        const isError = type === 'error';
        const iconClass = isError ? 'fa-exclamation-circle' : 'fa-check-circle';
        const bgClass = isError ? 'bg-red-50' : 'bg-green-50';
        const borderClass = isError ? 'border-red-400' : 'border-green-400';
        const textClass = isError ? 'text-red-800' : 'text-green-800';
        
        // Create toast with prominent styling
        toast.className = `mb-3 flex w-full max-w-md items-center rounded-lg border-2 px-4 py-3 shadow-lg ${bgClass} ${borderClass}`;
        toast.innerHTML = `
            <div class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg">
                <i class="fas ${iconClass} text-lg ${textClass}"></i>
            </div>
            <div class="ml-3 mr-4 text-sm font-medium ${textClass}">
                ${message}
            </div>
            <button class="ml-auto -mx-1.5 -my-1.5 rounded-lg p-1.5 inline-flex h-8 w-8 ${textClass} hover:bg-gray-200 focus:outline-none" 
                   onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        // Add to container
        toastContainer.appendChild(toast);
        
        // Auto-remove after 8 seconds (longer for better visibility)
        setTimeout(() => {
            if (toast.parentElement) {
                toast.remove();
            }
        }, 8000);
        
        return toast;
    }

    // Direct form submission handling
    document.addEventListener('DOMContentLoaded', function() {
        // Make sure the label input always triggers Alpine.js updates
        const labelInput = document.getElementById('label');
        if (labelInput) {
            labelInput.addEventListener('input', function(e) {
                // This will trigger Alpine's reactivity
            });
        }

        // Display toast immediately if message exists
        <?php if ($forceToast): ?>
        displayToast(<?php echo json_encode($toast_message); ?>, <?php echo json_encode($toast_type); ?>);
        <?php endif; ?>
        
        // Add listener to form submission to guarantee toast display
        const labelForm = document.querySelector('form');
        if (labelForm) {
            labelForm.addEventListener('submit', function() {
                // Store that we're submitting the form
                localStorage.setItem('label_form_submitted', 'true');
            });
        }
        
        // Check if we just came back from a form submission
        if (localStorage.getItem('label_form_submitted') === 'true') {
            // Clear the flag
            localStorage.removeItem('label_form_submitted');
            
            // Get debug values
            const debugMessage = document.getElementById('toast-message-debug')?.textContent;
            const debugType = document.getElementById('toast-type-debug')?.textContent;
            
            if (debugMessage) {
                console.log('Found debug message:', debugMessage);
                displayToast(debugMessage, debugType || 'success');
            }
        }
    });
    
    // Manual test function
    function testToasts() {
        displayToast('Test success message', 'success');
        setTimeout(() => {
            displayToast('Test error message', 'error');
        }, 1000);
    }
</script>