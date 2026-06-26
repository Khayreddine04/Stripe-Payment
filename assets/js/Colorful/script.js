// Consolidated Payment Form JavaScript
(function() {
  "use strict";

  let stripe = null;
  let elements = null;
  let cardNumber = null;
  let cardExpiry = null;
  let cardCvc = null;
  let isInitialized = false;

  // Initialize Stripe
  function initStripe() {
      const publicKey = window.stripePublicKey;

      if (!publicKey || publicKey.includes('YOUR_STRIPE')) {
          console.error("Stripe public key not configured properly");
          showError("Payment system is temporarily unavailable. Please try again later.");
          return false;
      }

      try {
          stripe = Stripe(publicKey);
          elements = stripe.elements();
          return true;
      } catch (error) {
          console.error("Error initializing Stripe:", error);
          showError("Failed to initialize payment system.");
          return false;
      }
  }

  // Initialize Stripe Elements
  function initStripeElements() {
      const elementStyle = {
          base: {
              color: "#1f2937",
              fontFamily: '"Inter", "Segoe UI", Roboto, sans-serif',
              fontSize: "16px",
              fontWeight: "500",
              "::placeholder": {
                  color: "#9ca3af",
              },
          },
          invalid: {
              color: "#dc2626",
              iconColor: "#dc2626",
          }
      };

      try {
          // Create card elements
          cardNumber = elements.create("cardNumber", {
              style: elementStyle,
              showIcon: true
          });

          cardExpiry = elements.create("cardExpiry", {
              style: elementStyle
          });

          cardCvc = elements.create("cardCvc", {
              style: elementStyle
          });

          // Mount elements
          cardNumber.mount("#card-number-element");
          cardExpiry.mount("#card-expiry-element");
          cardCvc.mount("#card-cvc-element");

          // Add event listeners for real-time validation
          cardNumber.on('change', handleElementChange);
          cardExpiry.on('change', handleElementChange);
          cardCvc.on('change', handleElementChange);

          return true;
      } catch (error) {
          console.error("Error creating Stripe elements:", error);
          return false;
      }
  }

  // Handle element change events
  function handleElementChange(event) {
      const displayError = document.getElementById("card-errors");
      if (!displayError) return;

      if (event.error) {
          displayError.textContent = event.error.message;
          displayError.style.display = "block";
      } else {
          displayError.textContent = "";
          displayError.style.display = "none";
      }

      // Update element styling
      const element = event.element;
      if (element) {
          const elementContainer = element._component?._element;
          if (elementContainer) {
              elementContainer.classList.toggle('StripeElement--complete', event.complete);
              elementContainer.classList.toggle('StripeElement--error', !!event.error);
          }
      }
  }

  // Initialize form submission
  function initForm() {
      const form = document.getElementById("payment_form");
      if (!form) {
          console.error("Payment form not found");
          return;
      }

      form.addEventListener("submit", handleFormSubmit);
  }

  // Handle form submission
  async function handleFormSubmit(event) {
      event.preventDefault();
      event.stopPropagation();
      const form = event.target;
      const submitButton = form.querySelector('button[type="submit"]');

      // Validate basic form fields
      if (!validateForm(form)) {
          return false;
      }

      setLoading(true, submitButton);

      try {
          // Get billing details from form
          const billingDetails = {
              name: form.querySelector('[name="full_name"]')?.value || '',
              email: form.querySelector('[name="email"]')?.value || '',
              address: {
                  line1: form.querySelector('[name="address1"]')?.value || '',
                  line2: form.querySelector('[name="address2"]')?.value || '',
                  city: form.querySelector('[name="city"]')?.value || '',
                  state: form.querySelector('[name="state"]')?.value || '',
                  postal_code: form.querySelector('[name="zip"]')?.value || '',
                  country: form.querySelector('[name="country"]')?.value || '',
              },
          };

          console.log("Creating payment method...");
          
          // Create payment method
          const { paymentMethod, error: paymentMethodError } = await stripe.createPaymentMethod({
              type: "card",
              card: cardNumber,
              billing_details: billingDetails,
          });

          if (paymentMethodError) {
              throw new Error(paymentMethodError.message);
          }

          console.log("Payment method created:", paymentMethod.id);

          // Add payment method to form
          addHiddenField(form, 'stripe_payment_method', paymentMethod.id);
          addHiddenField(form, 'stripeToken', paymentMethod.id);

          // Add service name for subscription creation
          const serviceName = form.querySelector('input[name="pt_service_name"]')?.value || 
                            form.querySelector('input[name="service"]')?.value || 
                            'Subscription';
          addHiddenField(form, 'pt_service_name', serviceName);
          console.log("Service name set to:", serviceName);

          // Check if this is a trial subscription
          const isTrial = form.querySelector('input[name="pt_is_trial"]')?.value === 'y';
          if (isTrial) {
              console.log("Processing trial subscription...");
              // Ensure subscription flag is set
              addHiddenField(form, 'pt_subscription', '1');
              addHiddenField(form, 'pt_payment_type', 'recurring');
              
              // Log trial details for debugging
              const trialDays = form.querySelector('input[name="pt_trial_days"]')?.value;
              const subscriptionAmount = form.querySelector('input[name="pt_subscription_amount"]')?.value;
              const billingFrequency = form.querySelector('input[name="pt_billing_frequency"]')?.value || 'monthly';
              
              console.log(`Trial subscription: ${trialDays} days trial, then $${subscriptionAmount} ${billingFrequency}`);
              
              if (trialDays) {
                  addHiddenField(form, 'pt_trial_days', trialDays);
                  console.log(`Trial period: ${trialDays} days`);
              }
              
              if (subscriptionAmount) {
                  addHiddenField(form, 'pt_subscription_amount', subscriptionAmount);
                  console.log(`Subscription amount after trial: $${subscriptionAmount}`);
              }
              
              addHiddenField(form, 'pt_billing_frequency', billingFrequency);
              console.log(`Billing frequency: ${billingFrequency}`);
          }

          // Submit form
          console.log("Submitting form with payment details...");
          form.submit();

      } catch (error) {
          console.error("Payment error:", error);
          showError(error.message || "An error occurred while processing your payment.");
          setLoading(false, submitButton);
      }
  }

  // Get billing details from form
  function getBillingDetails(form) {
      return {
          name: form.querySelector('[name="full_name"]')?.value || '',
          email: form.querySelector('[name="email"]')?.value || '',
          address: {
              city: form.querySelector('[name="city"]')?.value || '',
              postal_code: form.querySelector('[name="zip"]')?.value || '',
              country: form.querySelector('[name="country"]')?.value || '',
          }
      };
  }

  // Add hidden field to form
  function addHiddenField(form, name, value) {
      let input = form.querySelector(`input[name="${name}"]`);
      if (!input) {
          input = document.createElement('input');
          input.type = 'hidden';
          input.name = name;
          form.appendChild(input);
      }
      input.value = value;
  }

  // Validate form fields
  function validateForm(form) {
      const requiredFields = form.querySelectorAll('[required]');
      let isValid = true;

      requiredFields.forEach(field => {
          if (!field.value.trim()) {
              field.classList.add('is-invalid');
              isValid = false;
          } else {
              field.classList.remove('is-invalid');
          }
      });

      // Validate email format
      const emailField = form.querySelector('[type="email"]');
      if (emailField && emailField.value) {
          if (!isValidEmail(emailField.value)) {
              emailField.classList.add('is-invalid');
              isValid = false;
          }
      }

      if (!isValid) {
          showError("Please fill in all required fields correctly.");
      }

      return isValid;
  }

  // Email validation
  function isValidEmail(email) {
      const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      return re.test(email);
  }

  // Show error message
  function showError(message) {
      let errorDiv = document.getElementById("card-errors");
      
      if (!errorDiv) {
          errorDiv = document.createElement('div');
          errorDiv.id = 'card-errors';
          errorDiv.className = 'alert alert-danger mt-3';
          errorDiv.setAttribute('role', 'alert');
          
          const form = document.getElementById('payment_form');
          const submitBtn = form.querySelector('button[type="submit"]');
          if (submitBtn) {
              submitBtn.parentNode.insertBefore(errorDiv, submitBtn);
          }
      }

      errorDiv.textContent = message;
      errorDiv.style.display = 'block';

      // Auto-hide after 8 seconds
      setTimeout(() => {
          errorDiv.style.display = 'none';
      }, 8000);
  }

  // Set loading state
  function setLoading(loading, button) {
      if (!button) return;

      if (loading) {
          button.disabled = true;
          button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
      } else {
          button.disabled = false;
          button.innerHTML = 'Complete Payment';
      }
  }

  // Initialize everything
  function init() {
      if (isInitialized) return;

      console.log("Initializing payment form...");

      // Wait for Stripe.js to load
      if (typeof Stripe === 'undefined') {
          console.error("Stripe.js not loaded");
          setTimeout(init, 100);
          return;
      }

      if (!initStripe()) {
          return;
      }

      if (!initStripeElements()) {
          showError("Failed to initialize payment form. Please refresh the page.");
          return;
      }

      initForm();

      // Initialize card icons
      initializeCardIcons();

      isInitialized = true;
      console.log("Payment form initialized successfully");
  }

  // Initialize card icons interaction
  function initializeCardIcons() {
      const cardIcons = document.querySelectorAll('.card-icon');
      cardIcons.forEach(icon => {
          icon.addEventListener('click', function() {
              cardIcons.forEach(i => i.classList.remove('active'));
              this.classList.add('active');
          });
      });
  }

  // Start initialization when DOM is ready
  if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', init);
  } else {
      init();
  }

})();