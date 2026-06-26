class FormHandler {
    constructor() {
        this.currentStep = 1;
        this.formData = {};
        this.urlParams = new URLSearchParams(window.location.search);
        this.paymentFormLoaded = false;
        
        // Initialize form steps
        this.step1 = document.getElementById('personal-info-step');
        this.step2 = document.getElementById('payment-info-step');
        
        // Initialize Stripe
        this.stripe = Stripe(stripePublicKey);
        this.elements = this.stripe.elements();
        this.card = null;
        
        // Bind event listeners
        this.bindEvents();
        
        // Initialize form with URL parameters
        this.initializeForm();
        
        // Update step display
        this.updateStepDisplay();
        
        // Initialize Stripe elements
        this.initializeStripeElements();
    }
    
    // Initialize Stripe Elements for card input
    initializeStripeElements() {
        const style = {
            base: {
                color: '#32325d',
                fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
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

        // Create card element
        this.card = this.elements.create('card', { style: style });
        
        // Mount card element
        const cardElement = document.getElementById('card-element');
        if (cardElement) {
            this.card.mount('#card-element');
            
            // Handle real-time validation
            this.card.on('change', (event) => {
                const displayError = document.getElementById('card-errors');
                if (event.error) {
                    displayError.textContent = event.error.message;
                    displayError.style.display = 'block';
                } else {
                    displayError.textContent = '';
                    displayError.style.display = 'none';
                }
            });
        }
    }
    
    // Set up event listeners
    bindEvents() {
        // Handle form submission
        const form = document.getElementById('payment-form');
        if (form) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                // Handle navigation based on current step
                if (this.currentStep === 1) {
                    // Validate step 1
                    if (this.validateStep(1)) {
                        this.saveFormData();
                        this.currentStep = 2;
                        this.updateStepDisplay();
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                } else if (this.currentStep === 2) {
                    // Handle payment submission
                    await this.handlePayment();
                }
            });
        }
        
        // Handle back button if exists
        const backButton = document.getElementById('back-button');
        if (backButton) {
            backButton.addEventListener('click', (e) => {
                e.preventDefault();
                if (this.currentStep > 1) {
                    this.currentStep--;
                    this.updateStepDisplay();
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            });
        }
    }
    
    // Initialize form with URL parameters
    initializeForm() {
        this.urlParams.forEach((value, key) => {
            // Only process parameters that start with 'pt_'
            if (key.startsWith('pt_')) {
                const element = document.getElementById(key);
                if (element) {
                    // Decode URI component to handle special characters
                    const decodedValue = decodeURIComponent(value.replace(/\+/g, ' '));
                    element.value = decodedValue;
                    
                    // Dispatch change event to trigger any validation
                    element.dispatchEvent(new Event('change'));
                    
                    // If this is a select element, trigger change event after a short delay
                    if (element.tagName === 'SELECT') {
                        setTimeout(() => {
                            element.dispatchEvent(new Event('change'));
                        }, 100);
                    }
                }
            }
        });
        
        // Check if we should show a specific step from URL
        const stepParam = this.urlParams.get('step');
        if (stepParam && (stepParam === '1' || stepParam === '2')) {
            this.currentStep = parseInt(stepParam);
        }
    }
    
    // Validate form fields for the current step
    validateStep(step) {
        if (step === 1) {
            const requiredFields = [
                { id: 'pt_name', name: 'Full Name' },
                { id: 'pt_email', name: 'Email Address' },
                { id: 'pt_phone', name: 'Phone Number' }
            ];
            
            let isValid = true;
            
            // Validate required fields
            requiredFields.forEach(field => {
                const element = document.getElementById(field.id);
                if (element) {
                    const value = element.value.trim();
                    const parent = element.closest('.form-group') || element.parentElement;
                    let errorElement = parent.querySelector('.invalid-feedback');
                    
                    // Create error element if it doesn't exist
                    if (!errorElement) {
                        errorElement = document.createElement('div');
                        errorElement.className = 'invalid-feedback';
                        parent.appendChild(errorElement);
                    }
                    
                    // Clear previous errors
                    element.classList.remove('is-invalid');
                    errorElement.textContent = '';
                    
                    // Check if field is required and empty
                    if ((element.required || field.required) && !value) {
                        isValid = false;
                        element.classList.add('is-invalid');
                        errorElement.textContent = `${field.name} is required`;
                    } 
                    // Email validation
                    else if (field.id === 'pt_email' && value) {
                        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                        if (!emailRegex.test(value)) {
                            isValid = false;
                            element.classList.add('is-invalid');
                            errorElement.textContent = 'Please enter a valid email address';
                        }
                    }
                }
            });
            
            return isValid;
        }
        return true; // Default to true for other steps
    }
    
    // Save form data to this.formData object
    saveFormData() {
        const form = document.getElementById('payment-form');
        if (form) {
            const formData = new FormData(form);
            formData.forEach((value, key) => {
                this.formData[key] = value;
            });
        }
    }
    
    // Handle payment submission
    async handlePayment() {
        const submitButton = document.getElementById('submit-button');
        const resultContainer = document.getElementById('card-errors');
        
        try {
            // Disable the submit button to prevent multiple submissions
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
            
            // Create payment method
            const { paymentMethod, error } = await this.stripe.createPaymentMethod({
                type: 'card',
                card: this.card,
                billing_details: {
                    name: this.formData.pt_name || '',
                    email: this.formData.pt_email || ''
                }
            });
            
            if (error) {
                // Show error to customer
                resultContainer.textContent = error.message;
                resultContainer.style.display = 'block';
                submitButton.disabled = false;
                submitButton.textContent = 'Pay $' + document.querySelector('.amount').textContent;
                return;
            }
            
            // Add the payment method to the form
            const form = document.getElementById('payment-form');
            const paymentMethodInput = document.createElement('input');
            paymentMethodInput.setAttribute('type', 'hidden');
            paymentMethodInput.setAttribute('name', 'payment_method');
            paymentMethodInput.setAttribute('value', paymentMethod.id);
            form.appendChild(paymentMethodInput);
            
            // Submit the form
            form.submit();
            
        } catch (err) {
            console.error('Error:', err);
            resultContainer.textContent = 'An unexpected error occurred. Please try again.';
            resultContainer.style.display = 'block';
            submitButton.disabled = false;
            submitButton.textContent = 'Pay $' + document.querySelector('.amount').textContent;
        }
    }
    
    // Update the UI to show the current step
    updateStepDisplay() {
        // Hide all steps first
        if (this.step1) this.step1.style.display = 'none';
        if (this.step2) this.step2.style.display = 'none';
        
        // Show current step
        if (this.currentStep === 1 && this.step1) {
            this.step1.style.display = 'block';
            
            // Update button text
            const submitButton = document.querySelector('#payment-form button[type="submit"]');
            if (submitButton) {
                submitButton.textContent = 'Continue to Payment';
            }
        } else if (this.currentStep === 2 && this.step2) {
            this.step2.style.display = 'block';
            
            // Set focus on card element when showing payment form
            setTimeout(() => {
                if (this.card) {
                    const cardElement = document.querySelector('#card-element');
                    if (cardElement) cardElement.focus();
                }
            }, 100);
            
            // Update button text
            const submitButton = document.querySelector('#payment-form button[type="submit"]');
            if (submitButton) {
                submitButton.textContent = `Pay $${document.querySelector('.amount').textContent}`;
            }
        }
        
        // Update progress bar
        this.updateProgressBar();
    }
    
    // Update the progress bar based on current step
    updateProgressBar() {
        const progressBar = document.querySelector('.progress-bar');
        if (progressBar) {
            const progressPercent = (this.currentStep - 1) * 50; // 0% or 50% for 2 steps
            progressBar.style.width = `${progressPercent}%`;
            
            // Update active step indicator
            const steps = document.querySelectorAll('.progress-step');
            steps.forEach((step, index) => {
                if (index < this.currentStep) {
                    step.classList.add('active');
                } else {
                    step.classList.remove('active');
                }
            });
        }
    }
}

// Initialize form handler when the DOM is fully loaded
document.addEventListener('DOMContentLoaded', () => {
    window.formHandler = new FormHandler();
});
