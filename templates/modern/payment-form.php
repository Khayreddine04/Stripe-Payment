<?php
/**
 * Modern Payment Form Template
 */

// Debug the server variables
error_log("HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'NOT SET'));
error_log("SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'NOT SET'));
error_log("HTTPS: " . ($_SERVER['HTTPS'] ?? 'NOT SET'));
error_log("SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'NOT SET'));

// Get base URL
$base_url = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$base_url .= '://' . $_SERVER['HTTP_HOST'];
$base_url .= str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
?>

<div class="min-h-screen bg-gray-50 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-8">
            <?php if(!empty($logo)): ?>
                <img src="<?php echo $logo; ?>" alt="Logo" class="h-16 mx-auto mb-4">
            <?php endif; ?>
            <h1 class="text-3xl font-bold text-gray-900">Secure Payment</h1>
            <p class="mt-2 text-gray-600">Complete your purchase with confidence</p>
        </div>

        <?php if(!empty($notice)): ?>
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6 rounded">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            <?php echo $notice; ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($messages)): ?>
            <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6 rounded">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700">
                            <?php echo $messages; ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (isset($show_form) && $show_form): ?>
            <form class="bg-white shadow-lg rounded-lg overflow-hidden" id="payment_form" method="post">
                <input type="hidden" name="pt_action" value="do_payment">
                <input type="hidden" name="pt_tax_rate" id="pt_tax_rate" value="0" />
                <input type="hidden" name="pt_tax_exempt" id="pt_tax_exempt" value="n"/>
                
                <!-- Order Information -->
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Order Information</h2>
                    <div class="space-y-4">
                        <div>
                            <label for="pt_service" class="block text-sm font-medium text-gray-700 mb-1">Select Service</label>
                            <select class="mt-1 block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md" 
                                    name="pt_service" id="pt_service" required>
                                <option value="">Please select a service</option>
                                <?php echo isset($payment) && method_exists($payment, 'getHTMLServicesList') ? $payment->getHTMLServicesList() : ''; ?>
                            </select>
                            <div id="pt_service_description" class="mt-1 text-sm text-gray-500"></div>
                        </div>
                
                        <div id="pt_amount_container" class="hidden">
                            <label for="pt_amount" class="block text-sm font-medium text-gray-700 mb-1">
                                <span id="donation_period"></span>Amount
                            </label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">$</span>
                                </div>
                                <input type="text" 
                                       class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-7 pr-12 sm:text-sm border-gray-300 rounded-md" 
                                       id="pt_amount" 
                                       name="pt_amount" 
                                       placeholder="0.00" 
                                       value=""
                                       required
                                       data-rule-number="true" 
                                       data-msg-number="Please enter a valid number">
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm" id="currency">
                                        USD
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (isset($settings->show_description) && $settings->show_description == 'y'): ?>
                            <div>
                                <label for="pt_description" class="block text-sm font-medium text-gray-700 mb-1">
                                    Additional Notes (Optional)
                                </label>
                                <textarea name="pt_description" 
                                          id="pt_description" 
                                          class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 mt-1 block w-full sm:text-sm border border-gray-300 rounded-md" 
                                          rows="2"
                                          placeholder="Any special instructions or notes about your order"><?php echo htmlspecialchars($pt_description) ?></textarea>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Billing Information -->
                <div class="p-6 border-b border-gray-200" id="billing_info">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Billing Information</h2>
                    <div class="grid grid-cols-1 gap-y-4 gap-x-6 sm:grid-cols-6">
                        <div class="sm:col-span-3">
                            <label for="pt_name" class="block text-sm font-medium text-gray-700">Full Name *</label>
                            <input type="text" 
                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" 
                                   id="pt_name" 
                                   name="pt_name" 
                                   value="<?php echo htmlspecialchars($pt_name) ?>" 
                                   required>
                        </div>
                        
                        <div class="sm:col-span-4">
                            <label for="pt_email" class="block text-sm font-medium text-gray-700">Email Address *</label>
                            <input type="email" 
                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" 
                                   id="pt_email" 
                                   name="pt_email" 
                                   value="<?php echo htmlspecialchars($pt_email) ?>" 
                                   required>
                        </div>
                        
                        <div class="sm:col-span-6">
                            <label for="pt_address1" class="block text-sm font-medium text-gray-700">Street Address *</label>
                            <input type="text" 
                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" 
                                   id="pt_address1" 
                                   name="pt_address1" 
                                   value="<?php echo htmlspecialchars($pt_address1) ?>" 
                                   required>
                        </div>
                        
                        <div class="sm:col-span-6">
                            <label for="pt_address2" class="block text-sm font-medium text-gray-700">Apt, suite, etc. (optional)</label>
                            <input type="text" 
                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" 
                                   id="pt_address2" 
                                   name="pt_address2" 
                                   value="<?php echo htmlspecialchars($pt_address2) ?>">
                        </div>
                        
                        <div class="sm:col-span-2">
                            <label for="pt_city" class="block text-sm font-medium text-gray-700">City *</label>
                            <input type="text" 
                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" 
                                   id="pt_city" 
                                   name="pt_city" 
                                   value="<?php echo htmlspecialchars($pt_city) ?>" 
                                   required>
                        </div>
                        
                        <div class="sm:col-span-2">
                            <label for="pt_state" class="block text-sm font-medium text-gray-700">State/Province *</label>
                            <input type="text" 
                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" 
                                   id="pt_state" 
                                   name="pt_state" 
                                   value="<?php echo htmlspecialchars($pt_state) ?>" 
                                   required>
                        </div>
                        
                        <div class="sm:col-span-2">
                            <label for="pt_zip" class="block text-sm font-medium text-gray-700">ZIP/Postal Code *</label>
                            <input type="text" 
                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" 
                                   id="pt_zip" 
                                   name="pt_zip" 
                                   value="<?php echo htmlspecialchars($pt_zip) ?>" 
                                   required>
                        </div>
                        
                        <div class="sm:col-span-3">
                            <label for="pt_country" class="block text-sm font-medium text-gray-700">Country *</label>
                            <select id="pt_country" 
                                    name="pt_country" 
                                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                                    required>
                                <option value="">Select a country</option>
                                <?php echo $countries_html; ?>
                            </select>
                        </div>
                        
                        <div class="sm:col-span-3">
                            <label for="pt_phone" class="block text-sm font-medium text-gray-700">Phone</label>
                            <input type="tel" 
                                   class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" 
                                   id="pt_phone" 
                                   name="pt_phone" 
                                   value="<?php echo htmlspecialchars($pt_phone) ?>"
                                   placeholder="+1 (555) 123-4567">
                        </div>
                    </div>
                </div>
                
                <!-- Payment Information -->
                <div class="p-6 border-b border-gray-200" id="payment_info">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Payment Information</h2>
                    <!-- Star Ratings -->
                    <div class="flex items-center justify-center mb-6">
                        <div class="flex items-center space-x-1">
                            <svg class="w-6 h-6 text-yellow-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118l-2.8-2.034c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                            <svg class="w-6 h-6 text-yellow-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118l-2.8-2.034c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                            <svg class="w-6 h-6 text-yellow-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118l-2.8-2.034c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                            <svg class="w-6 h-6 text-yellow-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118l-2.8-2.034c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                            <svg class="w-6 h-6 text-yellow-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118l-2.8-2.034c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                        </div>
                        <span class="ml-2 text-sm text-gray-600" data-i18n="reviews">5.0 (100+ reviews)</span>
                    </div>
                    <div class="space-y-4">
                        <div id="card-element-cont">
                            <div class="border border-gray-300 rounded-md px-3 py-2 focus-within:ring-1 focus-within:ring-indigo-500 focus-within:border-indigo-500">
                                <div id="card-element" class="py-2">
                                    <!-- A Stripe Element will be inserted here. -->
                                </div>
                            </div>
                            <div id="card-errors" class="mt-2 text-sm text-red-600" role="alert"></div>
                        </div>
                    </div>
                </div>

                <!-- Terms and Conditions -->
                <?php if (isset($settings->enable_terms) && $settings->enable_terms == 'y'): ?>
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input id="pt_terms" 
                                       name="pt_terms" 
                                       type="checkbox" 
                                       value="1" 
                                       class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded"
                                       required>
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="pt_terms" class="font-medium text-gray-700">
                                    I agree to the <a href="#" class="text-indigo-600 hover:text-indigo-500" data-toggle="modal" data-target="#terms_and_conditions">Terms and Conditions</a>
                                </label>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Order Summary & Submit -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                        <div class="mb-4 sm:mb-0">
                            <div class="text-sm text-gray-600">Total Amount:</div>
                            <div class="flex items-baseline">
                                <span id="pt_total_amount" class="text-2xl font-bold text-indigo-600">0</span>
                                <span class="ml-1 text-gray-600" id="pt_currency_text">USD</span>
                                <span id="pt_recurring_period" class="ml-2 text-sm text-gray-500"></span>
                                <span id="pt_trial_text" class="ml-2 text-sm text-green-600"></span>
                            </div>
                        </div>
                        <div class="space-y-3 sm:space-y-0 sm:space-x-3">
                            <button type="submit" 
                                    id="form_submit_button" 
                                    class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                                <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                                </svg>
                                <span id="card_payment_button_cont">Pay Now</span>
                                <span id="paypal_payment_button_cont" class="hidden">
                                    <span>Continue to</span> <span class="font-bold">PayPal</span>
                                </span>
                            </button>
                            <div id="payment-request-button" class="w-full sm:w-auto">
                                <!-- A Stripe Element will be inserted here. -->
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-sm text-gray-500">
                        <svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <span>Your payment information is secure and encrypted</span>
                    </div>
                </div>
            </form>
        <?php else: ?>
            <!-- Success Message -->
            <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                <div class="px-6 py-12 text-center">
                    <div class="mx-auto flex items-center justify-center h-24 w-24 rounded-full bg-green-50 mb-6">
                        <svg class="h-12 w-12 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">
                        <?php echo function_exists('_tr') ? _tr('Payment Successful!') : 'Payment Successful!'; ?>
                    </h3>
                    <p class="text-gray-600 mb-6 max-w-md mx-auto">
                        <?php echo function_exists('_tr') ? _tr('Thank you for your payment. A confirmation has been sent to your email.') : 'Thank you for your payment. A confirmation has been sent to your email.'; ?>
                    </p>
                    <a href="<?php echo $base_url ?>" 
                       class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                        <?php echo function_exists('_tr') ? _tr('Return to Home') : 'Return to Home'; ?>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Terms and Conditions Modal -->
<div class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true" id="terms_and_conditions">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" data-dismiss="modal"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-6 w-6 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Terms and Conditions
                        </h3>
                        <div class="mt-4 max-h-96 overflow-y-auto pr-2">
                            <div class="text-sm text-gray-500 prose prose-indigo">
                                <?php echo nl2br(htmlspecialchars($settings->terms_content)); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" 
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm"
                        onclick="document.getElementById('terms_and_conditions').classList.add('hidden'); document.getElementById('pt_terms').checked = true;">
                    I Accept
                </button>
                <button type="button" 
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                        onclick="document.getElementById('terms_and_conditions').classList.add('hidden'); document.getElementById('pt_terms').checked = false;">
                    Decline
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="popup" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel"></h4>
            </div>
            <div class="modal-body">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for Stripe Elements -->
<script src="https://js.stripe.com/v3/"></script>
<script>
// Initialize Stripe Elements
var stripe = Stripe('<?php echo isset($settings->public_key) ? $settings->public_key : ''; ?>');
var elements = stripe.elements();

// Custom styling for the Stripe Element
var style = {
    base: {
        color: '#32325d',
        fontFamily: '"Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
        fontSmoothing: 'antialiased',
        fontSize: '16px',
        '::placeholder': {
            color: '#aab7c4'
        }
    },
    invalid: {
        color: '#fa755a',
        iconColor: '#fa755a'
    }
};

// Create an instance of the card Element
var card = elements.create('card', {
    style: style,
    hidePostalCode: true
});

// Add an instance of the card Element into the `card-element` <div>
card.mount('#card-element');

// Handle real-time validation errors from the card Element
card.on('change', function(event) {
    var displayError = document.getElementById('card-errors');
    if (event.error) {
        displayError.textContent = event.error.message;
        displayError.style.display = 'block';
    } else {
        displayError.textContent = '';
        displayError.style.display = 'none';
    }
});

// Handle form submission
var form = document.getElementById('payment-form');
form.addEventListener('submit', function(event) {
    event.preventDefault();
    
    // Disable the submit button to prevent repeated clicks
    var submitButton = document.getElementById('submit-button');
    var buttonText = submitButton.querySelector('.button-text');
    var spinner = submitButton.querySelector('.spinner-border');
    
    submitButton.disabled = true;
    buttonText.textContent = 'Processing...';
    spinner.classList.remove('d-none');
    
    // Create payment method and submit the form
    stripe.createPaymentMethod({
        type: 'card',
        card: card,
        billing_details: {
            name: document.getElementById('pt_name').value,
            email: document.getElementById('pt_email').value,
            address: {
                line1: document.getElementById('pt_address1').value,
                line2: document.getElementById('pt_address2').value,
                city: document.getElementById('pt_city').value,
                state: document.getElementById('pt_state').value,
                postal_code: document.getElementById('pt_postal').value,
                country: document.getElementById('pt_country').value,
            }
        }
    }).then(function(result) {
        if (result.error) {
            // Show error to your customer
            var errorElement = document.getElementById('card-errors');
            errorElement.textContent = result.error.message;
            errorElement.style.display = 'block';
            
            // Re-enable the submit button
            submitButton.disabled = false;
            buttonText.textContent = 'Pay Now';
            spinner.classList.add('d-none');
        } else {
            // Add the payment method ID to the form
            var hiddenInput = document.createElement('input');
            hiddenInput.setAttribute('type', 'hidden');
            hiddenInput.setAttribute('name', 'payment_method_id');
            hiddenInput.setAttribute('value', result.paymentMethod.id);
            form.appendChild(hiddenInput);
            
            // Submit the form
            form.submit();
        }
    });
});

// Form validation
(function() {
    'use strict';
    
    // Fetch all the forms we want to apply custom Bootstrap validation styles to
    var forms = document.querySelectorAll('.needs-validation');
    
    // Loop over them and prevent submission
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>

<!-- Close the main container and body/html tags if not already closed in other included files -->
</body>
</html>
