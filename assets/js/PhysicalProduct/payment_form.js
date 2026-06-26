// Consolidated Payment Form JavaScript
(function () {
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
    
    if (!publicKey || publicKey.includes("YOUR_STRIPE")) {
      console.error("Stripe public key not configured properly");
      showError(
        "Payment system is temporarily unavailable. Please try again later."
      );
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
      },
    };

    try {
      // Check if DOM elements exist before mounting
      const cardNumberEl = document.getElementById("card-number-element");
      const cardExpiryEl = document.getElementById("card-expiry-element");
      const cardCvcEl = document.getElementById("card-cvc-element");

      if (!cardNumberEl || !cardExpiryEl || !cardCvcEl) {
        console.error("Stripe element containers not found in DOM");
        return false;
      }

      // Create card elements
      cardNumber = elements.create("cardNumber", {
        style: elementStyle,
        showIcon: true,
      });

      cardExpiry = elements.create("cardExpiry", {
        style: elementStyle,
      });

      cardCvc = elements.create("cardCvc", {
        style: elementStyle,
      });

      // Mount elements
      cardNumber.mount("#card-number-element");
      cardExpiry.mount("#card-expiry-element");
      cardCvc.mount("#card-cvc-element");

      console.log("Stripe elements mounted successfully");

      // Add event listeners for real-time validation
      cardNumber.on("change", handleElementChange);
      cardExpiry.on("change", handleElementChange);
      cardCvc.on("change", handleElementChange);

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
        elementContainer.classList.toggle(
          "StripeElement--complete",
          event.complete
        );
        elementContainer.classList.toggle(
          "StripeElement--error",
          !!event.error
        );
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

    // Remove any existing submit listeners to avoid duplicates
    form.removeEventListener("submit", handleFormSubmit);
    form.addEventListener("submit", handleFormSubmit);
    
    console.log("Form submit handler attached");
  }

  // Handle form submission
  async function handleFormSubmit(event) {
    event.preventDefault();
    event.stopPropagation();
    
    const form = event.target;
    const submitButton = form.querySelector('button[type="submit"]');
    const errorElement = document.getElementById('card-errors');

    // Clear previous errors
    if (errorElement) {
      errorElement.textContent = '';
      errorElement.classList.remove('alert', 'alert-danger');
    }

    // Update hidden fields from visible form fields before validation
    if (typeof updatePaymentForm === 'function') {
      updatePaymentForm();
    }

    // Validate basic form fields
    if (!validateForm(form)) {
      return false;
    }

    // Check if Stripe Elements are properly initialized and mounted
    if (!stripe || !elements || !cardNumber) {
      showError("Payment system is not properly initialized. Please refresh the page and try again.");
      return false;
    }

    setLoading(true, submitButton);

    try {
      // Get billing details from form
      const billingDetails = {
        name: form.querySelector('[name="first_name"]')?.value + ' ' + form.querySelector('[name="last_name"]')?.value || 
              form.querySelector('#form_full_name')?.value || "",
        email: form.querySelector('[name="email"]')?.value || 
               form.querySelector('#form_email')?.value || "",
        address: {
          line1: form.querySelector('[name="address1"]')?.value || "",
          line2: form.querySelector('[name="address2"]')?.value || "",
          city: form.querySelector('[name="city"]')?.value || "",
          state: form.querySelector('[name="state"]')?.value || "",
          postal_code: form.querySelector('[name="zip"]')?.value || "",
          country: form.querySelector('[name="country"]')?.value || "",
        },
      };

      console.log("Creating payment method with billing details:", billingDetails);

      // Create payment method using the cardNumber element directly
      const { paymentMethod, error: paymentMethodError } = await stripe.createPaymentMethod({
        type: 'card',
        card: cardNumber,
        billing_details: billingDetails,
      });

      if (paymentMethodError) {
        throw new Error(paymentMethodError.message);
      }

      console.log("Payment method created:", paymentMethod.id);

      // Add payment method to form
      addHiddenField(form, "payment_method", paymentMethod.id);
      addHiddenField(form, "stripe_payment_method", paymentMethod.id);
      addHiddenField(form, "stripeToken", paymentMethod.id);

      // Get script URL for AJAX calls
      const scriptUrl = window.script_url || window.location.origin;

      // First, check if this is a recurring payment by calling get_stripe_payment_intent.php
      console.log("Checking payment intent...");
      const formData = new FormData(form);
      
      try {
        const intentResponse = await fetch(scriptUrl + "/backoffice/ajax/get_stripe_payment_intent.php", {
          method: "POST",
          body: new URLSearchParams(formData)
        });
        
        const intentData = await intentResponse.json();
        console.log("Payment intent response:", intentData);
        
        if (!intentData.res) {
          throw new Error(intentData.msg || "Failed to create payment intent");
        }

        // Check if this is a recurring payment
        if (intentData.processing === "RECUR") {
          console.log("Processing recurring payment/subscription...");
          
          // Call get_recurring.php to create the subscription
          const recurringResponse = await fetch(scriptUrl + "/backoffice/ajax/get_recurring.php", {
            method: "POST",
            body: new URLSearchParams(formData)
          });
          
          const recurringData = await recurringResponse.json();
          console.log("Recurring payment response:", recurringData);
          
          if (!recurringData.res) {
            throw new Error(recurringData.msg || "Failed to create subscription");
          }

          if (recurringData.subscription_obj) {
            const subscription = recurringData.subscription_obj;
            console.log("Subscription created:", subscription);

            // Handle different subscription statuses
            switch (subscription.status) {
              case "active":
                // Subscription is active, add subscription ID and submit
                addHiddenField(form, "subscription_id", subscription.id);
                console.log("Subscription active, submitting form...");
                HTMLFormElement.prototype.submit.call(form);
                break;

              case "trialing":
                console.log("Subscription in trial period");
                // Check if we need to confirm setup intent
                if (subscription.pending_setup_intent) {
                  const setupResult = await stripe.confirmCardSetup(
                    subscription.pending_setup_intent.client_secret
                  );
                  
                  if (setupResult.error) {
                    throw new Error(setupResult.error.message);
                  }
                  
                  if (setupResult.setupIntent.status === "succeeded") {
                    addHiddenField(form, "subscription_id", subscription.id);
                    console.log("Setup intent confirmed, submitting form...");
                    HTMLFormElement.prototype.submit.call(form);
                  }
                } else {
                  // No setup intent needed, just submit
                  addHiddenField(form, "subscription_id", subscription.id);
                  console.log("Trial subscription created, submitting form...");
                  HTMLFormElement.prototype.submit.call(form);
                }
                break;

              case "incomplete":
                console.log("Subscription incomplete, confirming payment...");
                // Need to confirm the payment
                const paymentResult = await stripe.confirmCardPayment(
                  subscription.latest_invoice.payment_intent.client_secret
                );
                
                if (paymentResult.error) {
                  throw new Error(paymentResult.error.message);
                }
                
                if (paymentResult.paymentIntent.status === "succeeded") {
                  addHiddenField(form, "subscription_id", subscription.id);
                  console.log("Payment confirmed, submitting form...");
                  HTMLFormElement.prototype.submit.call(form);
                }
                break;

              default:
                throw new Error(`Unknown subscription status: ${subscription.status}`);
            }
          }
        } else {
          // One-time payment
          console.log("Processing one-time payment...");
          const confirmResult = await stripe.confirmCardPayment(intentData.intent.client_secret, {
            payment_method: {
              card: cardNumber,
            },
            receipt_email: billingDetails.email,
          });

          if (confirmResult.error) {
            throw new Error(confirmResult.error.message);
          }

          if (confirmResult.paymentIntent.status === "succeeded") {
            addHiddenField(form, "stripeIntent", confirmResult.paymentIntent.id);
            console.log("Payment succeeded, submitting form...");
            HTMLFormElement.prototype.submit.call(form);
          }
        }
      } catch (fetchError) {
        console.error("AJAX error:", fetchError);
        throw fetchError;
      }
    } catch (error) {
      console.error("Payment error:", error);
      showError(
        error.message || "An error occurred while processing your payment."
      );
      setLoading(false, submitButton);
    }
  }

  // Get billing details from form
  function getBillingDetails(form) {
    return {
      name: form.querySelector('[name="full_name"]')?.value || "",
      email: form.querySelector('[name="email"]')?.value || "",
      address: {
        city: form.querySelector('[name="city"]')?.value || "",
        postal_code: form.querySelector('[name="zip"]')?.value || "",
        country: form.querySelector('[name="country"]')?.value || "",
      },
    };
  }

  // Add hidden field to form
  function addHiddenField(form, name, value) {
    let input = form.querySelector(`input[name="${name}"]`);
    if (!input) {
      input = document.createElement("input");
      input.type = "hidden";
      input.name = name;
      form.appendChild(input);
    }
    input.value = value;
  }

  // Validate form fields
  function validateForm(form) {
    const requiredFields = form.querySelectorAll("[required]");
    let isValid = true;

    requiredFields.forEach((field) => {
      if (!field.value.trim()) {
        field.classList.add("is-invalid");
        isValid = false;
      } else {
        field.classList.remove("is-invalid");
      }
    });

    // Validate email format
    const emailField = form.querySelector('[type="email"]');
    if (emailField && emailField.value) {
      if (!isValidEmail(emailField.value)) {
        emailField.classList.add("is-invalid");
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
      errorDiv = document.createElement("div");
      errorDiv.id = "card-errors";
      errorDiv.className = "alert alert-danger mt-3";
      errorDiv.setAttribute("role", "alert");

      const form = document.getElementById("payment_form");
      const submitBtn = form.querySelector('button[type="submit"]');
      if (submitBtn) {
        submitBtn.parentNode.insertBefore(errorDiv, submitBtn);
      }
    }

    errorDiv.textContent = message;
    errorDiv.style.display = "block";

    // Auto-hide after 8 seconds
    setTimeout(() => {
      errorDiv.style.display = "none";
    }, 8000);
  }

  // Set loading state
  function setLoading(loading, button) {
    if (!button) return;

    if (loading) {
      button.disabled = true;
      button.innerHTML =
        '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
    } else {
      button.disabled = false;
      button.innerHTML = "Complete Payment";
    }
  }

  // Cleanup existing Stripe elements
  function cleanupStripeElements() {
    if (cardNumber) {
      cardNumber.unmount();
      cardNumber = null;
    }
    if (cardExpiry) {
      cardExpiry.unmount();
      cardExpiry = null;
    }
    if (cardCvc) {
      cardCvc.unmount();
      cardCvc = null;
    }
  }

  // Initialize everything
  function init() {
    if (isInitialized) {
      console.log("Payment form already initialized");
      return;
    }

    console.log("Initializing payment form...");

    // Wait for Stripe.js to load
    if (typeof Stripe === "undefined") {
      console.error("Stripe.js not loaded, retrying...");
      setTimeout(init, 100);
      return;
    }

    // Cleanup any existing elements first
    cleanupStripeElements();

    if (!initStripe()) {
      console.error("Failed to initialize Stripe");
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
    const cardIcons = document.querySelectorAll(".card-icon");
    cardIcons.forEach((icon) => {
      icon.addEventListener("click", function () {
        cardIcons.forEach((i) => i.classList.remove("active"));
        this.classList.add("active");
      });
    });
  }

  // Start initialization when DOM is ready
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
