class ModernFormHandler {
    constructor() {
        this.currentStep = 1;
        this.formData = {};
        this.urlParams = new URLSearchParams(window.location.search);
        this.paymentFormLoaded = false;
        
        // Initialize form elements
        this.form = document.getElementById('payment-form');
        this.step1 = document.getElementById('step1') || document.getElementById('personal-info-step');
        this.step2 = document.getElementById('step2') || document.getElementById('payment-info-step');
        
        // Initialize navigation buttons
        this.nextBtn = document.getElementById('nextBtn') || document.getElementById('next-step');
        this.prevBtn = document.getElementById('backBtn') || document.getElementById('prev-step');
        this.submitBtn = document.getElementById('submitBtn') || document.getElementById('submit-form');
        
        // Initialize Stripe elements
        this.stripe = null;
        this.elements = null;
        this.cardNumber = null;
        this.cardExpiry = null;
        this.cardCvc = null;
        
        // Bind events and initialize
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.initializeFormWithURLParams();
        this.setupCardFormatting();
        this.updateStepDisplay();
        
        // Initialize Stripe if publishable key is available
        this.initializeStripeIfAvailable();
    }
    
    bindEvents() {
        // Navigation button events
        if (this.nextBtn) {
            this.nextBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleNextStep();
            });
        }
        
        if (this.prevBtn) {
            this.prevBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.handlePrevStep();
            });
        }
        
        // Form submission
        if (this.form) {
            this.form.addEventListener('submit', (e) => this.handleFormSubmit(e));
        }
        
        // Real-time validation
        const inputs = document.querySelectorAll('input, select');
        inputs.forEach(input => {
            input.addEventListener('blur', () => this.validateField(input));
            input.addEventListener('input', () => this.clearFieldError(input));
        });
        
        // Handle browser navigation
        window.addEventListener('popstate', (e) => {
            const step = parseInt(new URLSearchParams(window.location.search).get('step') || '1');
            if (step >= 1 && step <= 2) {
                this.currentStep = step;
                this.updateStepDisplay();
            }
        });
    }
    
    initializeFormWithURLParams() {
        // Map of URL parameter names to form field IDs
        const paramMapping = {
            'name': 'pt_name',
            'first_name': 'pt_name',
            'email': 'pt_email',
            'phone': 'pt_phone',
            'address1': 'pt_address1',
            'address_line_1': 'pt_address1',
            'address2': 'pt_address2', 
            'address_line_2': 'pt_address2',
            'city': 'pt_city',
            'state': 'pt_state',
            'zip': 'pt_postal',
            'postal': 'pt_postal',
            'country': 'pt_country',
            'cardholder_name': 'cardholderName'
        };
        
        let hasPrefilled = false;
        
        // Process URL parameters
        this.urlParams.forEach((value, key) => {
            const fieldId = paramMapping[key] || (key.startsWith('pt_') ? key : null);
            
            if (fieldId) {
                const element = document.getElementById(fieldId);
                if (element && value) {
                    const decodedValue = decodeURIComponent(value.replace(/\+/g, ' '));
                    element.value = decodedValue;
                    hasPrefilled = true;
                    
                    // Trigger validation
                    element.dispatchEvent(new Event('change'));
                    
                    // Special handling for select elements
                    if (element.tagName === 'SELECT') {
                        setTimeout(() => {
                            element.dispatchEvent(new Event('change'));
                        }, 100);
                    }
                }
            }
        });
        
        // Show prefill notice if applicable
        const prefilledNotice = document.getElementById('prefilledNotice');
        if (hasPrefilled && prefilledNotice) {
            prefilledNotice.style.display = 'block';
        }
        
        // Check URL for step parameter
        const stepParam = this.urlParams.get('step');
        if (stepParam && ['1', '2'].includes(stepParam)) {
            this.currentStep = parseInt(stepParam);
        }
    }
    
    setupCardFormatting() {
        // Card number formatting
        const cardNumberInput = document.getElementById('cardNumber');
        if (cardNumberInput) {
            cardNumberInput.addEventListener('input', (e) => {
                let value = e.target.value.replace(/\D/g, '');
                value = value.replace(/(\d{4})(?=\d)/g, '$1 ');
                e.target.value = value;
                this.detectCardType(value.replace(/\s/g, ''));
            });
        }
        
        // Expiry date formatting
        const expiryInput = document.getElementById('expiryDate');
        if (expiryInput) {
            expiryInput.addEventListener('input', (e) => {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length >= 2) {
                    value = value.substring(0, 2) + '/' + value.substring(2, 4);
                }
                e.target.value = value;
            });
        }
        
        // CVV numeric only
        const cvvInput = document.getElementById('cvv');
        if (cvvInput) {
            cvvInput.addEventListener('input', (e) => {
                e.target.value = e.target.value.replace(/\D/g, '');
            });
        }
    }
    
    detectCardType(cardNumber) {
        const cardIcon = document.getElementById('cardIcon');
        if (!cardIcon) return;
        
        const firstDigit = cardNumber.charAt(0);
        const firstTwoDigits = cardNumber.substring(0, 2);
        
        if (firstDigit === '4') {
            cardIcon.textContent = '💳'; // Visa
            cardIcon.title = 'Visa';
        } else if (firstTwoDigits >= '51' && firstTwoDigits <= '55') {
            cardIcon.textContent = '💳'; // Mastercard
            cardIcon.title = 'Mastercard';
        } else if (firstTwoDigits === '34' || firstTwoDigits === '37') {
            cardIcon.textContent = '💳'; // American Express
            cardIcon.title = 'American Express';
        } else {
            cardIcon.textContent = '💳';
            cardIcon.title = 'Credit Card';
        }
    }
    
    handleNextStep() {
        if (this.validateStep(this.currentStep)) {
            this.saveFormData();
            this.currentStep++;
            this.updateStepDisplay();
            
            // Load payment form if needed
            if (this.currentStep === 2 && !this.paymentFormLoaded) {
                this.loadPaymentFormContent();
            }
            
            // Smooth scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }
    
    handlePrevStep() {
        this.currentStep--;
        this.updateStepDisplay();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    
    validateStep(step) {
        if (step === 1) {
            return this.validatePersonalInfoStep();
        } else if (step === 2) {
            return this.validatePaymentStep();
        }
        return true;
    }
    
    validatePersonalInfoStep() {
        const requiredFields = [
            { id: 'pt_name', name: 'Full Name' },
            { id: 'pt_email', name: 'Email Address' }
        ];
        
        // Add conditional required fields based on show_billing setting
        const showBilling = document.getElementById('pt_address1');
        if (showBilling) {
            requiredFields.push(
                { id: 'pt_phone', name: 'Phone Number' },
                { id: 'pt_address1', name: 'Street Address' },
                { id: 'pt_city', name: 'City' },
                { id: 'pt_state', name: 'State' },
                { id: 'pt_postal', name: 'ZIP Code' },
                { id: 'pt_country', name: 'Country' }
            );
        }
        
        let isValid = true;
        const errors = [];
        
        requiredFields.forEach(field => {
            const element = document.getElementById(field.id);
            if (element && element.offsetParent !== null) { // Check if element is visible
                if (!this.validateField(element)) {
                    isValid = false;
                    errors.push(field.name);
                }
            }
        });
        
        if (!isValid) {
            this.showFormError('Please fill in all required fields correctly.');
            
            // Focus first invalid field
            const firstInvalid = document.querySelector('.error, .is-invalid');
            if (firstInvalid) {
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstInvalid.focus();
            }
        }
        
        return isValid;
    }
    
    validatePaymentStep() {
        const requiredFields = [
            { id: 'cardNumber', name: 'Card Number' },
            { id: 'cardholderName', name: 'Cardholder Name' },
            { id: 'expiryDate', name: 'Expiry Date' },
            { id: 'cvv', name: 'CVV' }
        ];
        
        let isValid = true;
        
        requiredFields.forEach(field => {
            const element = document.getElementById(field.id);
            if (element && !this.validateField(element)) {
                isValid = false;
            }
        });
        
        if (!isValid) {
            this.showFormError('Please fill in all payment details correctly.');
        }
        
        return isValid;
    }
    
    validateField(field) {
        const value = field.value.trim();
        const fieldId = field.id;
        let isValid = true;
        let errorMessage = '';
        
        // Required field validation
        if (field.required && !value) {
            isValid = false;
            errorMessage = 'This field is required';
        }
        
        // Specific field validations
        if (value && isValid) {
            switch (fieldId) {
                case 'pt_email':
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(value)) {
                        isValid = false;
                        errorMessage = 'Please enter a valid email address';
                    }
                    break;
                    
                case 'cardNumber':
                    const cardNumber = value.replace(/\s/g, '');
                    if (cardNumber.length < 13 || cardNumber.length > 19 || !this.luhnCheck(cardNumber)) {
                        isValid = false;
                        errorMessage = 'Please enter a valid card number';
                    }
                    break;
                    
                case 'expiryDate':
                    const expiryRegex = /^(0[1-9]|1[0-2])\/\d{2}$/;
                    if (!expiryRegex.test(value)) {
                        isValid = false;
                        errorMessage = 'Please enter a valid expiry date (MM/YY)';
                    } else {
                        // Check expiration
                        const [month, year] = value.split('/');
                        const expiry = new Date(2000 + parseInt(year), parseInt(month) - 1);
                        const today = new Date();
                        today.setDate(1);
                        
                        if (expiry < today) {
                            isValid = false;
                            errorMessage = 'Card has expired';
                        }
                    }
                    break;
                    
                case 'cvv':
                    if (value.length < 3 || value.length > 4 || !/^\d+$/.test(value)) {
                        isValid = false;
                        errorMessage = 'CVV must be 3 or 4 digits';
                    }
                    break;
            }
        }
        
        // Update field appearance
        this.showFieldError(field, errorMessage, !isValid);
        
        return isValid;
    }
    
    luhnCheck(cardNumber) {
        let sum = 0;
        let shouldDouble = false;
        
        for (let i = cardNumber.length - 1; i >= 0; i--) {
            let digit = parseInt(cardNumber[i]);
            
            if (shouldDouble) {
                digit *= 2;
                if (digit > 9) {
                    digit -= 9;
                }
            }
            
            sum += digit;
            shouldDouble = !shouldDouble;
        }
        
        return (sum % 10) === 0;
    }
    
    showFieldError(field, message, hasError) {
        // Update field styling
        field.classList.toggle('error', hasError);
        field.classList.toggle('is-invalid', hasError);
        field.classList.toggle('is-valid', !hasError && field.value.trim());
        
        // Show/hide error message
        const errorElement = document.getElementById(field.id + 'Error') || 
                           field.parentNode.querySelector('.error-message') ||
                           field.parentNode.querySelector('.invalid-feedback');
                           
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.classList.toggle('show', hasError);
            errorElement.style.display = hasError ? 'block' : 'none';
        }
    }
    
    clearFieldError(field) {
        field.classList.remove('error', 'is-invalid');
        
        const errorElement = document.getElementById(field.id + 'Error') || 
                           field.parentNode.querySelector('.error-message') ||
                           field.parentNode.querySelector('.invalid-feedback');
                           
        if (errorElement) {
            errorElement.classList.remove('show');
            errorElement.style.display = 'none';
        }
    }
    
    saveFormData() {
        if (this.form) {
            const formData = new FormData(this.form);
            formData.forEach((value, key) => {
                this.formData[key] = value;
            });
        }
    }
    
    updateStepDisplay() {
        // Hide all steps first
        [this.step1, this.step2].forEach(step => {
            if (step) {
                step.classList.add('hidden');
                step.classList.remove('active');
            }
        });
        
        // Show current step
        const currentStepElement = this.currentStep === 1 ? this.step1 : this.step2;
        if (currentStepElement) {
            currentStepElement.classList.remove('hidden');
            currentStepElement.classList.add('active');
            // Force reflow to ensure transitions work
            currentStepElement.offsetHeight;
        }
        
        // Update progress bar
        const progressFill = document.getElementById('progressFill');
        if (progressFill) {
            const progressPercent = (this.currentStep / 2) * 100;
            progressFill.style.width = `${progressPercent}%`;
        }
        
        // Update step titles
        const stepTitle = document.getElementById('stepTitle');
        const stepSubtitle = document.getElementById('stepSubtitle');
        
        if (stepTitle) {
            stepTitle.textContent = this.currentStep === 1 ? 'Personal Information' : 'Payment Details';
        }
        
        if (stepSubtitle) {
            stepSubtitle.textContent = `Step ${this.currentStep} of 2`;
        }
        
        // Update navigation buttons
        if (this.prevBtn) {
            this.prevBtn.style.display = this.currentStep === 1 ? 'none' : 'inline-flex';
        }
        
        if (this.nextBtn) {
            this.nextBtn.style.display = this.currentStep === 2 ? 'none' : 'inline-flex';
        }
        
        if (this.submitBtn) {
            this.submitBtn.style.display = this.currentStep === 2 ? 'inline-flex' : 'none';
        }
        
        // Update progress indicators if they exist
        this.updateProgressIndicator();
        
        // Focus management
        setTimeout(() => {
            const firstInput = currentStepElement?.querySelector('input:not([type="hidden"]), select, textarea');
            if (firstInput && firstInput.offsetParent !== null) {
                firstInput.focus();
            }
        }, 100);
    }
    
    updateProgressIndicator() {
        const progress = document.querySelector('.progress');
        if (!progress) return;
        
        const steps = progress.querySelectorAll('.progress-step');
        steps.forEach((step, index) => {
            const stepNumber = index + 1;
            
            if (stepNumber < this.currentStep) {
                step.classList.add('completed');
                step.classList.remove('active');
            } else if (stepNumber === this.currentStep) {
                step.classList.add('active');
                step.classList.remove('completed');
            } else {
                step.classList.remove('active', 'completed');
            }
        });
        
        progress.setAttribute('data-steps', this.currentStep);
    }
    
    loadPaymentFormContent() {
        if (this.paymentFormLoaded || !this.step2) return;
        
        // Check if payment form content needs to be loaded dynamically
        if (this.step2.innerHTML.trim() === '' || this.step2.textContent.includes('Loading')) {
            this.step2.innerHTML = `
                <div class="text-center py-4">
                    <div class="loading"></div>
                    <p class="mt-2">Loading payment form...</p>
                </div>
            `;
            
            // Simulate loading payment form content
            setTimeout(() => {
                // This would normally load content from payment_info.php
                this.initializeStripeIfAvailable();
                this.paymentFormLoaded = true;
            }, 500);
        }
    }
    
    initializeStripeIfAvailable() {
        const stripeKey = document.querySelector('meta[name="stripe-publishable-key"]')?.content;
        if (!stripeKey) return;
        
        try {
            this.stripe = Stripe(stripeKey);
            this.elements = this.stripe.elements();
            
            // Initialize Stripe Elements if containers exist
            this.initializeStripeElements();
        } catch (error) {
            console.error('Stripe initialization failed:', error);
        }
    }
    
    initializeStripeElements() {
        const style = {
            base: {
                color: '#32325d',
                fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                fontSmoothing: 'antialiased',
                fontSize: '16px',
                '::placeholder': { color: '#aab7c4' }
            },
            invalid: {
                color: '#dc3545',
                iconColor: '#dc3545'
            }
        };
        
        // Create Stripe elements
        const cardNumberElement = document.getElementById('card-number-element');
        const cardExpiryElement = document.getElementById('card-expiry-element');
        const cardCvcElement = document.getElementById('card-cvc-element');
        
        if (cardNumberElement) {
            this.cardNumber = this.elements.create('cardNumber', { style, showIcon: true });
            this.cardNumber.mount('#card-number-element');
            this.setupStripeEventHandlers(this.cardNumber, 'card-number');
        }
        
        if (cardExpiryElement) {
            this.cardExpiry = this.elements.create('cardExpiry', { style });
            this.cardExpiry.mount('#card-expiry-element');
            this.setupStripeEventHandlers(this.cardExpiry, 'card-expiry');
        }
        
        if (cardCvcElement) {
            this.cardCvc = this.elements.create('cardCvc', { style });
            this.cardCvc.mount('#card-cvc-element');
            this.setupStripeEventHandlers(this.cardCvc, 'card-cvc');
        }
    }
    
    setupStripeEventHandlers(element, elementType) {
        element.on('change', (event) => {
            const errorElement = document.getElementById(`${elementType}-errors`);
            if (errorElement) {
                if (event.error) {
                    errorElement.textContent = event.error.message;
                    errorElement.style.display = 'block';
                } else {
                    errorElement.textContent = '';
                    errorElement.style.display = 'none';
                }
            }
        });
    }
    
    async handleFormSubmit(e) {
        e.preventDefault();
        
        // Handle step navigation
        if (this.currentStep === 1) {
            this.handleNextStep();
            return;
        }
        
        // Handle payment submission
        if (this.currentStep === 2) {
            await this.handlePaymentSubmit(e);
        }
    }
    
    async handlePaymentSubmit(e) {
        // Validate payment step
        if (!this.validateStep(2)) {
            return;
        }
        
        const submitButton = this.submitBtn;
        if (!submitButton) return;
        
        // Show loading state
        const originalText = submitButton.innerHTML;
        submitButton.disabled = true;
        submitButton.innerHTML = `
            <div class="loading"></div>
            <span>Processing...</span>
        `;
        
        // Add loading class to form container
        const formContainer = document.querySelector('.form-container');
        if (formContainer) {
            formContainer.classList.add('loading');
        }
        
        try {
            // If using Stripe, create payment method
            if (this.stripe && this.cardNumber) {
                const { paymentMethod, error } = await this.createStripePaymentMethod();
                
                if (error) {
                    throw new Error(error.message);
                }
                
                // Add payment method to form
                let paymentMethodInput = document.getElementById('payment_method');
                if (!paymentMethodInput) {
                    paymentMethodInput = document.createElement('input');
                    paymentMethodInput.type = 'hidden';
                    paymentMethodInput.name = 'payment_method';
                    paymentMethodInput.id = 'payment_method';
                    this.form.appendChild(paymentMethodInput);
                }
                paymentMethodInput.value = paymentMethod.id;
            }
            
            // Submit form to PHP backend
            setTimeout(() => {
                this.form.submit();
            }, 1000);
            
        } catch (error) {
            console.error('Payment error:', error);
            this.showFormError(error.message || 'An error occurred processing your payment. Please try again.');
            
            // Restore button state
            submitButton.disabled = false;
            submitButton.innerHTML = originalText;
            
            // Remove loading state
            if (formContainer) {
                formContainer.classList.remove('loading');
            }
        }
    }
    
    async createStripePaymentMethod() {
        const billingDetails = {
            name: document.getElementById('pt_name')?.value || document.getElementById('cardholderName')?.value || '',
            email: document.getElementById('pt_email')?.value || '',
            phone: document.getElementById('pt_phone')?.value || '',
            address: {
                line1: document.getElementById('pt_address1')?.value || '',
                line2: document.getElementById('pt_address2')?.value || '',
                city: document.getElementById('pt_city')?.value || '',
                state: document.getElementById('pt_state')?.value || '',
                postal_code: document.getElementById('pt_postal')?.value || '',
                country: document.getElementById('pt_country')?.value || ''
            }
        };
        
        return await this.stripe.createPaymentMethod({
            type: 'card',
            card: this.cardNumber,
            billing_details: billingDetails
        });
    }
    
    showFormError(message) {
        const errorContainer = document.getElementById('form-errors');
        if (errorContainer) {
            const messageElement = errorContainer.querySelector('.error-message') || errorContainer;
            messageElement.textContent = message;
            errorContainer.classList.remove('d-none');
            errorContainer.style.display = 'block';
            
            // Scroll to error
            errorContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // Auto-hide after 8 seconds
            setTimeout(() => {
                errorContainer.classList.add('d-none');
                errorContainer.style.display = 'none';
            }, 8000);
        }
    }
    
    hideFormError() {
        const errorContainer = document.getElementById('form-errors');
        if (errorContainer) {
            errorContainer.classList.add('d-none');
            errorContainer.style.display = 'none';
        }
    }
    
    // Cleanup method
    destroy() {
        // Unmount Stripe elements
        if (this.cardNumber) {
            this.cardNumber.destroy();
            this.cardNumber = null;
        }
        if (this.cardExpiry) {
            this.cardExpiry.destroy();
            this.cardExpiry = null;
        }
        if (this.cardCvc) {
            this.cardCvc.destroy();
            this.cardCvc = null;
        }
        
        // Remove event listeners
        if (this.nextBtn) {
            this.nextBtn.replaceWith(this.nextBtn.cloneNode(true));
        }
        if (this.prevBtn) {
            this.prevBtn.replaceWith(this.prevBtn.cloneNode(true));
        }
        if (this.form) {
            this.form.replaceWith(this.form.cloneNode(true));
        }
    }
    
    // Utility methods for existing PHP integration
    static initialize() {
        // Initialize when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                window.formHandler = new ModernFormHandler();
            });
        } else {
            window.formHandler = new ModernFormHandler();
        }
    }
}

// Auto-initialize the form handler
ModernFormHandler.initialize();

// Maintain compatibility with existing code
class FormHandler extends ModernFormHandler {
    constructor() {
        super();
        console.warn('FormHandler is deprecated. Use ModernFormHandler instead.');
    }
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ModernFormHandler;
}

// jQuery integration for existing validation
if (typeof $ !== 'undefined') {
    $(document).ready(function() {
        // Initialize jQuery validation if it exists
        if ($.fn.validate) {
            $('#payment-form').validate({
                errorElement: 'div',
                errorClass: 'invalid-feedback',
                highlight: function(element) {
                    $(element).addClass('is-invalid').removeClass('is-valid');
                },
                unhighlight: function(element) {
                    $(element).removeClass('is-invalid').addClass('is-valid');
                },
                errorPlacement: function(error, element) {
                    if (element.parent('.input-group').length) {
                        error.insertAfter(element.parent());
                    } else {
                        error.insertAfter(element);
                    }
                },
                submitHandler: function(form) {
                    // Let the ModernFormHandler handle submission
                    return false;
                }
            });
        }
    });
}