"use strict";

function ptCheckoutAjaxUrl(path) {
  var endpoint = path.charAt(0) === "/" ? path : "/" + path;
  var base = typeof script_url === "string" ? script_url.replace(/\/+$/, "") : "";

  if (!base) {
    return endpoint;
  }

  try {
    var parsed = new URL(base, window.location.origin);
    if (parsed.origin !== window.location.origin) {
      return endpoint;
    }
    return parsed.origin === window.location.origin && parsed.pathname === "/"
      ? endpoint
      : base + endpoint;
  } catch (e) {
    return endpoint;
  }
}

function ptNormalizeAdaptivePaymentFields(form) {
  var $form = $(form || "#payment_form");
  if (!$form.length || !$(".adaptive-offer-page").length) {
    return;
  }

  var amount = $form.find("#pt_amount").last().val();
  var currency = $form.find("#pt_currency").last().val();
  var country = $form.find("#pt_country").last().val();
  var symbol = $form.find("input[name='pt_currency_symbol']").last().val();
  var position = $form.find("input[name='pt_currency_position']").last().val() || "before";

  if (amount !== undefined && amount !== "") {
    $form.find("input[name='amount'], input[name='pt_amount']").val(amount);
  }
  if (currency !== undefined && currency !== "") {
    $form.find("input[name='currency'], input[name='pt_currency']").val(currency);
  }
  if (country !== undefined && country !== "") {
    $form.find("input[name='pt_country'], select[name='country']").val(country);
  }
  if (symbol !== undefined) {
    $form.find("input[name='pt_currency_symbol']").val(symbol);
  }
  $form.find("input[name='pt_currency_position']").val(position);
}

// Progress Bar State Management
function updateProgressBar(step) {
  const progressBar = document.querySelector(".progressbar");
  const step1Indicator = document.getElementById("step1-indicator");
  const step2Indicator = document.getElementById("step2-indicator");

  if (step === 1) {
    // Reset to step 1
    progressBar.classList.remove("step2");
    step1Indicator.classList.add("active");
    step1Indicator.classList.remove("completed");
    step2Indicator.classList.remove("active", "completed");

    // Reset progress line
    progressBar.style.setProperty("--progress-width", "0%");
    progressBar.style.setProperty("--progress-color", "#4f46e5");
  } else if (step === 2) {
    // Move to step 2
    progressBar.classList.add("step2");
    step1Indicator.classList.remove("active");
    step1Indicator.classList.add("completed");
    step2Indicator.classList.add("active");

    // Animate progress line
    progressBar.style.setProperty("--progress-width", "100%");
    progressBar.style.setProperty("--progress-color", "#4f46e5");
  }
}

function checkCaptcha(e) {
  $("#payment_form button[type='submit']").removeAttr("disabled", "disabled");
}

/*function stripeTokenHandler(token) {
  var form = document.getElementById('payment_form');
  var hiddenInput = document.createElement('input');
  hiddenInput.setAttribute('type', 'hidden');
  hiddenInput.setAttribute('name', 'stripeToken');
  hiddenInput.setAttribute('value', token.id);
  form.appendChild(hiddenInput);

  // Submit the form
  form.submit();
}*/

function stripeIntentHandler(intent) {
  var form = document.getElementById("payment_form");
  ptNormalizeAdaptivePaymentFields(form);
  var hiddenInput = document.createElement("input");
  hiddenInput.setAttribute("type", "hidden");
  hiddenInput.setAttribute("name", "stripeIntent");
  hiddenInput.setAttribute("value", intent);
  form.appendChild(hiddenInput);

  /* Submit the form*/
  form.submit();
}

function stripeButtonHandler() {
  var form = document.getElementById("payment_form");
  var hiddenInput = document.createElement("input");
  hiddenInput.setAttribute("type", "hidden");
  hiddenInput.setAttribute("name", "stripeButton");
  hiddenInput.setAttribute("value", "y");
  form.appendChild(hiddenInput);
}

function stripePaymentMethodHandler(id) {
  $("#payment_form input[name='payment_method']").remove();
  var form = document.getElementById("payment_form");
  var hiddenInput = document.createElement("input");
  hiddenInput.setAttribute("type", "hidden");
  hiddenInput.setAttribute("name", "payment_method");
  hiddenInput.setAttribute("value", id);
  form.appendChild(hiddenInput);
}

function stripeSubscriptionHandler(id) {
  var form = document.getElementById("payment_form");
  ptNormalizeAdaptivePaymentFields(form);

  // Add URL parameters to form data
  const urlParams = new URLSearchParams(window.location.search);
  const clickid = urlParams.get("clickid");
  const source = urlParams.get("source");

  if (clickid) {
    const clickidInput = document.createElement("input");
    clickidInput.setAttribute("type", "hidden");
    clickidInput.setAttribute("name", "clickid");
    clickidInput.setAttribute("value", clickid);
    form.appendChild(clickidInput);
  }

  if (source) {
    const sourceInput = document.createElement("input");
    sourceInput.setAttribute("type", "hidden");
    sourceInput.setAttribute("name", "source");
    sourceInput.setAttribute("value", source);
    form.appendChild(sourceInput);
  }

  const hiddenInput = document.createElement("input");
  hiddenInput.setAttribute("type", "hidden");
  hiddenInput.setAttribute("name", "subscription_id");
  hiddenInput.setAttribute("value", id);
  form.appendChild(hiddenInput);
  ptNormalizeAdaptivePaymentFields(form);
  form.submit();
}

/*function stripeSourceHandler(source) {
  // Redirect the customer to the authorization URL.
  document.location.href = source.redirect.url;
}*/

function stripeSourceHandler(source) {
  // Insert the source ID into the form so it gets submitted to the server
  var form = document.getElementById("payment_form");
  var hiddenInput = document.createElement("input");
  hiddenInput.setAttribute("type", "hidden");
  hiddenInput.setAttribute("name", "stripeSource");
  hiddenInput.setAttribute("value", source.id);
  form.appendChild(hiddenInput);

  /* Submit the form */
  form.submit();
}

function getServiceAmount(item_val) {
  let fee_total = 0;
  if (fee_enabled === "y" && $("#pt_fee_amount").length) {
    /* service fee is enabled */
    /* get the fee type */
    if (fee_type === 1) {
      /*percentage*/
      fee_total = (item_val * fee_amount) / 100;
    } else if (fee_type === 2) {
      /*amount*/
      fee_total = fee_amount;
    }
    return fee_total;
  } else {
    return 0;
  }
}

function getTaxAmount(amount, scenario) {
  /* 1. check if tax is enabled */
  let subtotal = 0;
  let tax_enabled = false;
  if (tax_rate > 0 && $("#pt_subtotal_amount").length) {
    tax_enabled = true;
  }

  if (!tax_enabled) {
    return 0;
  } else {
    /* The following split into scenarios is not required in current version of the tax settings,
     * however, it will be needed in future. */
    if (scenario === "custom-value") {
      /*console.log('processing custom-value scenario');*/
      /* Custom value cannot be "exempt" from taxes */
      subtotal = amount * tax_rate;
    }
    if (scenario === "service-selection-and-currency-change") {
      /* do we need to check for exemption */
      /*console.log('processing service-selection-and-currency-change scenario');*/
      subtotal = amount * tax_rate;
    }
    if (scenario === "donation-service-selection") {
      /* Donation amount cannot be exempt from taxes, at least in v1 of Tax Module. */
      /* If terminal is setup stricly for donations - don't enable taxes at all then. */
      /*console.log('processing donation-service-selection scenario');*/
      subtotal = amount * tax_rate;
    }
    if (scenario === "item-selection") {
      /* any product or service selection */
      /* customer can select exempt product, must check */
      /*console.log('processing item-selection');*/
      subtotal = amount * tax_rate;
    }
    if (scenario === "pre-selected-service") {
      /* when accessing direct url for the service/item payment */
      /* must check service/product exemption */
      /*console.log('processing pre-selected-service');*/
      subtotal = amount * tax_rate;
    }
    if (scenario === "payment-plan-service") {
      /* when selecting product with payment plan option */
      /* must check service/product exemption */
      /*console.log('processing payment-plan-service');*/
      subtotal = amount * tax_rate;
    }
    if (scenario === "payment-plan-service-select-installment-slider-action") {
      /* when selecting product with payment plan option */
      /* must check service/product exemption */
      /*console.log('processing payment-plan-service-select-installment-slider-action');*/
      subtotal = amount * tax_rate;
    }
    if (scenario === "payment-plan-service-select-installment-slider-stop") {
      /* when selecting product with payment plan option */
      /* must check service/product exemption */
      /*console.log('processing payment-plan-service-select-installment-slider-stop');*/
      subtotal = amount * tax_rate;
    }

    let product = $("#pt_service").find("option:selected");
    $.ajax({
      url: ptCheckoutAjaxUrl("/backoffice/ajax/check_tax_exempt.php"),
      data: product,
      type: "POST",
      async: false,
      dataType: "text",
      success: function (data) {
        if (data === "y") {
          /* exempt product */
          $("#pt_subtotal_amount").html("(Exempt)");
          /* reset subtotal to 0 */
          subtotal = 0;
        } else if (data === "n") {
          /* non exempt, taxable */
          $("#pt_subtotal_amount").html(subtotal.toFixed(2));
          /* finally return the subtotal value (contains just the tax) */
        }
      },
    });

    return subtotal;
  }
}

$().ready(function () {
  // Error handler for form submission
  $(document).ajaxError(function() {
    $("#payment_form button[type='submit']").prop("disabled", false);
    $("#payment_form").removeClass("loading");
  });


    // Wait for translations to load before initializing Stripe (for locale)
  // Default to 'en' if translationsPromise is not available
  var stripeLocale = 'en';
  if (typeof getLanguage === 'function') {
      stripeLocale = getLanguage();
  } else if (window.translationsPromise) {
      // We can't easily get the lang from the promise result synchronously, 
      // but we can get it from the URL or navigator as done in translations.js
      // For now, let's try to get it from the global scope if exposed, or re-calculate
      let urlParams = new URLSearchParams(window.location.search);
      stripeLocale = urlParams.get("lang") || navigator.language.slice(0, 2).toLowerCase();
  }

  var elements = stripe.elements({ locale: stripeLocale });
  /* Stripe card element  */
  var style = {
    base: {
      color: "#32325d",
      fontFamily: '"Roboto",sans-serif',
      fontSize: "16px",
      "::placeholder": {
        color: "#555",
      },
    },
    invalid: {
      color: "#fa755a",
      iconColor: "#fa755a",
    },
  };

  // Unified error handler
  function handleCardErrors(event) {
    var displayError = document.getElementById("card-errors");
    if (displayError) {
      var msg = event && event.error ? event.error.message : "";
      displayError.textContent = msg;
      displayError.style.display = msg ? "block" : "none";
    }
  }

  // Initialize Stripe Elements with retry if containers aren't yet in DOM
  var cardRef = null; // points to either cardNumber (split) or card (combined)
  var initAttempts = 0;
  function initStripeElements() {
    var hasSplit = !!document.getElementById("card-number-element");
    var hasCombined = !!document.getElementById("card-element");

    if (hasSplit) {
      // Split Elements for Minimalist theme
      var cardNumber = elements.create("cardNumber", { style: style });
      cardNumber.mount("#card-number-element");

      var cardExpiry = elements.create("cardExpiry", { style: style });
      cardExpiry.mount("#card-expiry-element");

      var cardCvc = elements.create("cardCvc", { style: style });
      cardCvc.mount("#card-cvc-element");

      cardNumber.addEventListener("change", handleCardErrors);
      cardExpiry.addEventListener("change", handleCardErrors);
      cardCvc.addEventListener("change", handleCardErrors);

      cardRef = cardNumber;
      return true;
    } else if (hasCombined) {
      // Combined Element for light/green themes
      var card = elements.create("card", {
        hidePostalCode: true,
        style: style,
      });
      card.mount("#card-element");
      card.addEventListener("change", handleCardErrors);
      cardRef = card;
      return true;
    }

    // Containers not present yet: retry a few times before giving up
    if (initAttempts < 10) {
      initAttempts++;
      setTimeout(initStripeElements, 150);
    } else {
      if (window.console && console.warn) {
        console.warn("Stripe card element containers not found after retries.");
      }
    }
    return false;
  }

  initStripeElements();
  /* Stripe card element */

  // Ensure error container is hidden at start
  $("#card-errors").hide();

  $("input#pt_amount").bind("paste", function (e) {
    var el = $(this);
    setTimeout(function () {
      var text = $(el).val();
      checkNumHighlight(text);
    }, 100);
  });
  $("input#pt_shipping_same").bind("change", function (e) {
    if ($(this).is(":checked")) {
      $("#shipping_info").slideUp();
    } else {
      $("#shipping_info").slideDown();
    }
  });
  /* Custom value entering scenario */
  $("input#pt_amount").on("change", function (e) {
    var val = parseFloat($(this).val());
    val = isNaN(val) || 0 >= val ? 0 : val;
    /* TAX PROCESSING, IF ENABLED */
    var service_fee = getServiceAmount(val);
    var tax_subtotal = getTaxAmount(val + service_fee, "custom-value");
    var total = val + tax_subtotal + service_fee;
    $("#pt_total_amount").html(total.toFixed(2));
    create_gpay_button(total);
  });

  /* var currency_rate = false; */
  $("select#pt_currency").bind("change", function (e) {
    /*var tax_rate = ($("input[name='pt_tax_rate']").val() * 1)/100;*/
    var selected_currency = $(this).val();
    var symbol = $(this).find("option:selected").data("symbol");
    var paypal = $(this).find("option:selected").data("enable_paypal");
    if (paypal === "0") {
      $("#pt_type1").attr("disabled", "disabled");
      $("#pt_type").prop("checked", true).trigger("click");
    } else {
      $("#pt_type1").removeAttr("disabled");
    }
    $("input[name='pt_currency_symbol']").val(symbol);

    if (currency_rate.res) {
      if ($("select#pt_service").length /*&&  !is_donation()*/) {
        if (typeof currency_rate.rate[selected_currency] !== "undefined") {
          $(".pt_currency_symbol").html(symbol);
          $(".pt_currency_text").html(selected_currency);
          var amount = parseFloat(
            $("select#pt_service").find("option:selected").data("amount")
          );
          amount = isNaN(amount) || 0 >= amount ? 0 : amount;
          amount = amount * currency_rate.rate[selected_currency];
          $("select#pt_service option").each(function () {
            if (typeof $(this).data("amount") !== "undefined") {
              var start_of_price = $(this).text().lastIndexOf("( ");
              var end_of_price = $(this)
                .text()
                .indexOf(" ", start_of_price + 2);
              $(this).text(
                $(this)
                  .text()
                  .substr(0, start_of_price + 2) +
                  symbol +
                  (
                    $(this).data("amount") *
                    currency_rate.rate[selected_currency]
                  ).toFixed(2) +
                  $(this).text().substr(end_of_price)
              );
            }
          });
          var service_fee = getServiceAmount(amount);
          var tax_subtotal = getTaxAmount(
            amount + service_fee,
            "service-selection-and-currency-change"
          );
          $("#pt_total_amount").html(
            (amount + tax_subtotal + service_fee).toFixed(2)
          );
        } else {
          pt_popup(
            "Currency converter error",
            "This currency is not supported by currency converter"
          );
          $("select#pt_currency").val(currency_rate.base).trigger("change");
        }
      } else {
        $(".pt_currency_symbol").html(symbol);
        $(".pt_currency_text").html(selected_currency);
      }
    } else if (
      selected_currency != currency_rate.base &&
      $("select#pt_service").length &&
      !is_donation()
    ) {
      pt_popup("Currency converter error", currency_rate.mess);
      $("select#pt_currency").val(currency_rate.base).trigger("change");
    }

    $("select#pt_service").trigger("change");
  });

  if ($("input#pt_amount").length) {
    $("input#pt_amount").trigger("keyup");
  }

  if ($("select#pt_currency").length) {
    $("select#pt_currency").trigger("change");
  }
  /* SERVICE PAYMENT SCENARIO */
  $("select#pt_service").on("change", function (e) {
    var service_description = $(this)
      .find("option:selected")
      .data("description");
    $("#pt_service_description").html(service_description);
    if (is_donation()) {
      $("#pt_amount").show();
      $("#donation_period").html(get_donation_period());
      $("#pt_recurring_period").html(get_donation_period());
      var selected_amount = parseFloat($("input#pt_amount").val());
      selected_amount =
        isNaN(selected_amount) || 0 >= selected_amount ? 0 : selected_amount;
      var service_fee = getServiceAmount(selected_amount);
      var tax_subtotal = getTaxAmount(
        selected_amount + service_fee,
        "donation-service-selection"
      );
      $("#pt_total_amount").html(
        (selected_amount + tax_subtotal + service_fee).toFixed(2)
      );
      $("#pt_payments_cont").hide();
      let selectedOption = $(this).find("option:selected");
    } else {
      let selectedOption = $(this).find("option:selected");
      var selected_amount = parseFloat(selectedOption.data("amount"));
      selected_amount = isNaN(selected_amount) ? 0 : selected_amount;

      if (selectedOption.data("plan") === 1) {
        $("#pt_payments_cont").show();
        fillPayments(
          selectedOption.data("pmin"),
          selectedOption.data("pmax"),
          selected_amount
        );
      } else {
        $("#pt_payments_cont").hide();
      }

      if (selectedOption.data("interval")) {
        $("#pt_recurring_period").html(selectedOption.data("interval"));
      } else {
        $("#pt_recurring_period").html("");
      }

      if (selectedOption.data("trial")) {
        $("#pt_trial_text").html(
          "after " + selectedOption.data("trial") + " day(s) trial"
        );
      } else {
        $("#pt_trial_text").html("");
      }

      if ($("select#pt_currency").length) {
        var currency = $("select#pt_currency").val();

        if (currency_rate.res) {
          if (typeof currency_rate.rate[currency] !== "undefined") {
            selected_amount = selected_amount * currency_rate.rate[currency];
          }
        }
      }

      let service_fee = getServiceAmount(selected_amount);
      let tax_subtotal = getTaxAmount(
        selected_amount + service_fee,
        "item-selection"
      );
      /*console.log(tax_subtotal);*/
      let service_total =
        selected_amount * 1 + tax_subtotal * 1 + service_fee * 1;
      $("#pt_total_amount").html(service_total.toFixed(2));
      $("#pt_amount").hide();

      if (selectedOption.val() && !selectedOption.data("recurring")) {
        create_gpay_button(service_total, selectedOption.html());
      } else {
        $("#payment-request-button").hide();
      }
      $("#pt_type").trigger("click");
    }
  });
  if ($("select#pt_service").length) {
    $("select#pt_service").trigger("change");
  }

  if ($("#pt_service_amount").length) {
    let pt_service_amount = $("#pt_service_amount").val() * 1;
    let service_fee = getServiceAmount(pt_service_amount);
    let tax_subtotal = getTaxAmount(
      pt_service_amount + service_fee,
      "pre-selected-service"
    );
    let service_total = pt_service_amount + tax_subtotal + service_fee;
    $("#pt_total_amount").html(service_total.toFixed(2));

    if (!$("#pt_service_recurring").val())
      create_gpay_button(service_total, $("#pt_service_name").html());
  }

  if ($("#idInvoice").length) {
    let invoice_total = $("#pt_invoice_amount").val() * 1;
    if (!$("#pt_invoice_recurring").val())
      create_gpay_button(
        invoice_total,
        "Order# " + $("#pt_invoice_number").val()
      );
  }


  /* validate the form when it is submitted */
  $(".validate").validate({
    submitHandler: function (form) {
      if ($("#pt_terms").length && !$("#pt_terms").is(":checked")) {
        pt_popup("Terms and Conditions", $("#pt_terms").attr("title"));
        return false;
      }

      // Declare URL parameters once at the beginning of submitHandler
      const urlParams = new URLSearchParams(window.location.search);
      const clickid = urlParams.get("clickid");
      const source = urlParams.get("source");

        if (
          $("input#pt_type1").is(":checked") ||
          $("input#pt_type3").is(":checked")
        ) {
          ptNormalizeAdaptivePaymentFields(form);

          if (clickid) {
          const clickidInput = document.createElement("input");
          clickidInput.setAttribute("type", "hidden");
          clickidInput.setAttribute("name", "clickid");
          clickidInput.setAttribute("value", clickid);
          form.appendChild(clickidInput);
        }

        if (source) {
          const sourceInput = document.createElement("input");
          sourceInput.setAttribute("type", "hidden");
          sourceInput.setAttribute("name", "source");
          sourceInput.setAttribute("value", source);
          form.appendChild(sourceInput);
        }

          form.submit();
        } else {
        // Disable submit button
        $("#payment_form button[type='submit']").prop("disabled", true);
        $("#payment_form").addClass("loading");

        // Remove any existing clickid/source inputs to avoid duplicates
        $("input[name='clickid']").not('[type="hidden"][value]').remove();
        $("input[name='source']").not('[type="hidden"][value]').remove();

        // Add them to the form if they exist in URL
        if (
          clickid &&
          !$("input[name='clickid'][value='" + clickid + "']").length
        ) {
          const clickidInput = document.createElement("input");
          clickidInput.setAttribute("type", "hidden");
          clickidInput.setAttribute("name", "clickid");
          clickidInput.setAttribute("value", clickid);
          form.appendChild(clickidInput);
        }

        if (
          source &&
          !$("input[name='source'][value='" + source + "']").length
        ) {
          const sourceInput = document.createElement("input");
          sourceInput.setAttribute("type", "hidden");
          sourceInput.setAttribute("name", "source");
          sourceInput.setAttribute("value", source);
          form.appendChild(sourceInput);
        }

        ptNormalizeAdaptivePaymentFields(form);

        /* getting paymentIntentToken */
        $.ajax({
          url: ptCheckoutAjaxUrl("/backoffice/ajax/get_stripe_payment_intent.php"),
          data: $(form).serializeArray(),
          type: "POST",
          dataType: "json",
          success: function (data) {
            /*console.log(data);*/
            if (data.res) {
              if (data.processing === "RECUR") {
                stripe
                  .createPaymentMethod({
                    type: "card",
                    card: cardRef,
                    billing_details: {
                      name: $("#pt_name").val(),
                    },
                  })
                  .then(function (result) {
                    console.log(result);
                    if (result.error !== undefined) {
                      $("#card-errors").html(result.error.message).show();
                      $("#payment_form button[type='submit']").prop(
                        "disabled",
                        false
                      );
                      $("#payment_form").removeClass("loading");
                      return;
                    } else {
                      stripePaymentMethodHandler(result.paymentMethod.id);

                      // Add URL parameters to form data before sending to get_recurring.php
                      const urlParams = new URLSearchParams(window.location.search);
                      const clickid = urlParams.get("clickid");
                      const source = urlParams.get("source");

                      if (clickid) {
                        const clickidInput = document.createElement("input");
                        clickidInput.setAttribute("type", "hidden");
                        clickidInput.setAttribute("name", "clickid");
                        clickidInput.setAttribute("value", clickid);
                        form.appendChild(clickidInput);
                      }

                      if (source) {
                        const sourceInput = document.createElement("input");
                        sourceInput.setAttribute("type", "hidden");
                        sourceInput.setAttribute("name", "source");
                        sourceInput.setAttribute("value", source);
                       form.appendChild(sourceInput);
                      }

                      ptNormalizeAdaptivePaymentFields(form);

                      $.ajax({
                        url: ptCheckoutAjaxUrl("/backoffice/ajax/get_recurring.php"),
                        data: $(form).serializeArray(),
                        type: "POST",
                        dataType: "json",
                        success: function (data) {
                          console.log(data);
                          if (data.res) {
                            if (data.subscription_obj) {
                              console.log(data.subscription_obj);
                              switch (data.subscription_obj.status) {
                                case "active":
                                  // Redirect to account page
                                  stripeSubscriptionHandler(
                                    data.subscription_obj.id
                                  );
                                  break;

                                case "trialing":
                                  console.log(data.subscription_obj);
                                  console.log(
                                    "pending_setup_intent" in
                                      data.subscription_obj
                                  );
                                  if (
                                    null !==
                                    data.subscription_obj.pending_setup_intent
                                  ) {
                                    stripe
                                      .confirmCardSetup(
                                        data.subscription_obj
                                          .pending_setup_intent.client_secret
                                      )
                                      .then(function (result) {
                                        if (result.error) {
                                          /* Show error to your customer (e.g., insufficient funds)*/
                                          $("#card-errors")
                                            .html(result.error.message)
                                            .show();
                                          $(
                                            "#payment_form button[type='submit']"
                                          ).prop("disabled", false);
                                          $("#payment_form").removeClass(
                                            "loading"
                                          );
                                        } else {
                                          /* The payment has been processed! */
                                          console.log(result);
                                          if (
                                            result.setupIntent.status ===
                                            "succeeded"
                                          ) {
                                            stripeSubscriptionHandler(
                                              data.subscription_obj.id
                                            );
                                          }
                                        }
                                      });
                                  } else {
                                    stripeSubscriptionHandler(
                                      data.subscription_obj.id
                                    );
                                  }
                                  break;
                                case "incomplete":
                                  console.log(data.subscription_obj);
                                  stripe
                                    .confirmCardPayment(
                                      data.subscription_obj.latest_invoice
                                        .payment_intent.client_secret
                                    )
                                    .then(function (result) {
                                      if (result.error) {
                                        /* Show error to your customer (e.g., insufficient funds)*/
                                        $("#card-errors")
                                          .html(result.error.message)
                                          .show();
                                        $(
                                          "#payment_form button[type='submit']"
                                        ).prop("disabled", false);
                                        $("#payment_form").removeClass(
                                          "loading"
                                        );
                                      } else {
                                        /* The payment has been processed! */
                                        if (
                                          result.paymentIntent.status ===
                                          "succeeded"
                                        ) {
                                          /* Show a success message to your customer
                                                                                   There's a risk of the customer closing the window before callback execution
                                                                                   Set up a webhook or plugin to listen for the payment_intent.succeeded event
                                                                                   that handles any business critical post-payment actions */

                                          stripeSubscriptionHandler(
                                            data.subscription_obj.id
                                          );
                                        }
                                      }
                                    });
                                  break;
                                default:
                                  $("#card-errors")
                                    .html(
                                      `Unknown Subscription status: ${data.subscription_obj.status}`
                                    )
                                    .show();
                                  $("#payment_form button[type='submit']").prop(
                                    "disabled",
                                    false
                                  );
                                  $("#payment_form").removeClass("loading");
                                  return;
                              }
                            }
                          } else {
                            $("#card-errors").html(data.msg).show();
                            $("#payment_form button[type='submit']").prop(
                              "disabled",
                              false
                            );
                            $("#payment_form").removeClass("loading");
                            return;
                          }
                        },
                        error: function () {
                          $("#payment_form button[type='submit']").prop("disabled", false);
                          $("#payment_form").removeClass("loading");
                        },
                      });
                    }
                  });
              } else {
                stripe
                  .confirmCardPayment(data.intent.client_secret, {
                    payment_method: {
                      card: cardRef,
                    },
                    receipt_email: $("#pt_email").val(),
                  })
                  .then(function (result) {
                    /*console.log(result);*/
                    if (result.error) {
                      /* Show error to your customer (e.g., insufficient funds)*/
                      $("#card-errors").html(result.error.message).show();
                      $("#payment_form button[type='submit']").prop(
                        "disabled",
                        false
                      );
                      $("#payment_form").removeClass("loading");
                    } else {
                      /* The payment has been processed! */
                      if (result.paymentIntent.status === "succeeded") {
                        /* Show a success message to your customer
                                               There's a risk of the customer closing the window before callback execution
                                               Set up a webhook or plugin to listen for the payment_intent.succeeded event
                                               that handles any business critical post-payment actions */

                        stripeIntentHandler(result.paymentIntent.id);
                      }
                    }
                  });
              }
            } else {
              $("#card-errors").html(data.msg).show();
              $("#payment_form button[type='submit']").prop("disabled", false);
              $("#payment_form").removeClass("loading");
            }
          },
          error: function () {
            $("#payment_form button[type='submit']").prop("disabled", false);
            $("#payment_form").removeClass("loading");
          },
        });

        return false;
      }
    },
    errorPlacement: function (error, element) {
      element
        .parents(".form-group")
        .removeClass("has-success")
        .addClass("has-error");
      element.wrap("<div class='control-wrap'>");

      error.appendTo(element.parent());
    },
    errorElement: "b",
    wrapper: "em",
    success: function (label) {
      label
        .parents(".form-group")
        .removeClass("has-error")
        .addClass("has-success");
    },
    focusCleanup: true,
    highlight: function (element, errorClass) {
      $(element)
        .parents(".form-group")
        .removeClass("has-success")
        .addClass("has-error");
    },
  });

  $("input[name='pt_type']").click(function () {
    if ($(this).val() === "card") {
      $("#card_payment_button_cont").show();
      $("#paypal_payment_button_cont").hide();
      $("#card-element-cont").show();
      $("#form_submit_button").show();

      $("#billing_info").show();
      $("#shipping_info").show();
      $("#payment_info").show();
      $("#terms_cont").show();
      $("#payment-request-button").hide();
    } else if ($(this).val() === "cash") {
      $("#card_payment_button_cont").show();
      $("#paypal_payment_button_cont").hide();
      $("#card-element-cont").hide();

      $("#billing_info").show();
      $("#shipping_info").show();
      $("#payment_info").show();
      $("#terms_cont").show();
      $("#payment-request-button").hide();
    } else if ($(this).val() === "gpay") {
      $("#card_payment_button_cont").hide();
      $("#paypal_payment_button_cont").hide();
      $("#card-element-cont").hide();
      $("#form_submit_button").hide();

      $("#billing_info").hide();
      $("#shipping_info").hide();
      $("#payment_info").hide();
      $("#terms_cont").hide();
      $("#payment-request-button").show();
    } else if ($(this).val() === "paypal") {
      $("#card_payment_button_cont").hide();
      $("#paypal_payment_button_cont").show();
      $("#card-element-cont").hide();
      $("#form_submit_button").show();
      $("#billing_info").show();
      $("#shipping_info").show();
      $("#payment_info").hide();
      $("#terms_cont").show();
      $("#payment-request-button").hide();
      $("#paypal_payment_button_cont").show();
    }
  });

  $("#popup").on("show.bs.modal", function (event) {
    var button = $(event.relatedTarget);
    /* Button that triggered the modal*/
    var header = button.data("header");
    var text = button.data("text");
    var modal = $(this);
    modal.find(".modal-title").text(header);
    modal.find(".modal-body").html(text);
  });

  $("#pt_country").on("change", function () {
    var countryId = $(this).val();
    if (countryId != "") {
      $("#pt_state option:gt(0)").remove();
      getStatesByCountry(countryId, "pt_state");
    } else {
      $("#pt_state option:gt(0)").remove();
    }
  });
  $("#pt_country").trigger("change");

  $("#pt_country_s").on("change", function () {
    var countryId = $(this).val();
    if (countryId != "") {
      $("#pt_state_s option:gt(0)").remove();
      getStatesByCountry(countryId, "pt_state_s");
    } else {
      $("#pt_state_s option:gt(0)").remove();
    }
  });
  if ($("#pt_country_s").length) {
    $("#pt_country_s").trigger("change");
  }
  $("input#pt_amount").trigger("change");
});

function getStatesByCountry(countryId, statesContId) {
  $("#" + statesContId)
    .find("option:eq(0)")
    .html("Please wait..");
  $.ajax({
    url: ptCheckoutAjaxUrl("/backoffice/ajax/get_states.php"),
    data: { countryId: countryId, pt_state: $("#pt_state_or").val() },
    type: "POST",
    async: false,
    dataType: "text",
    success: function (data) {
      $("#" + statesContId)
        .find("option:eq(0)")
        .html("Please Select");
      $("#" + statesContId).append(data);
    },
    error: function () {
      $("#" + statesContId)
        .find("option:eq(0)")
        .html("Error. Please try again.");
    },
  });
}

function pt_popup(header, text) {
  $("#popup").find(".modal-title").text(header);
  $("#popup").find(".modal-body").html(text);
  $("#popup").modal("show");
}

function fillPayments(min, max, amount) {
  if (undefined !== document.sliderInitialized) {
    $("#pt_payments_count").slider("destroy");
  }

  if ($("select#pt_currency").length) {
    var currency = $("select#pt_currency").val();

    if (currency_rate.res) {
      if (typeof currency_rate.rate[currency] !== "undefined") {
        amount = amount * currency_rate.rate[currency];
      }
    }
  }

  let ticks = [];
  let ticks_labels = [];
  if (max > min) {
    let _amount = Math.round(amount / min).toFixed(2);
    $("#payment-info").html(getPaymentsText(min, _amount));
    var service_fee = getServiceAmount(_amount);
    var tax_subtotal = getTaxAmount(
      _amount + service_fee,
      "payment-plan-service"
    );
    $("#pt_total_amount").html(_amount + tax_subtotal + service_fee);
    for (let i = min; i <= max; i++) {
      ticks.push(i);
      if (i == min || i == max) {
        ticks_labels.push(i);
      } else {
        ticks_labels.push("");
      }
    }
    /*console.log(min);*/
    $("#pt_payments_count").val(min);
    $("#pt_payments_count").attr("data-slider-value", min);
    $("#pt_payments_count")
      .slider({
        ticks: ticks,
        ticks_snap_bounds: 1,
        ticks_labels: ticks_labels,
        min: min,
        max: max,
        step: 1,
        tooltip: "hide",
      })
      .on("slide", function (e) {
        //console.log(e)
        let _amount = (amount / e.value).toFixed(2);
        $("#payment-info").html(getPaymentsText(e.value, _amount));
        let service_fee = getServiceAmount(_amount);
        let tax_subtotal = getTaxAmount(
          _amount + service_fee,
          "payment-plan-service-select-installment-slider-action"
        );
        $("#pt_total_amount").html(
          (_amount * 1 + tax_subtotal * 1 + service_fee * 1).toFixed(2)
        );
      })
      .on("slideStop", function (e) {
        //console.log(e)
        let _amount = (amount / e.value).toFixed(2);
        $("#payment-info").html(getPaymentsText(e.value, _amount));
        /* $("#pt_total_amount").html(_amount); */
        let service_fee = getServiceAmount(_amount);
        let tax_subtotal = getTaxAmount(
          _amount + service_fee,
          "payment-plan-service-select-installment-slider-stop"
        );
        $("#pt_total_amount").html(
          (_amount * 1 + tax_subtotal * 1 + service_fee * 1).toFixed(2)
        );
      });
    document.sliderInitialized = true;
  }
}

function getPaymentsText(num, amount) {
  /*return "Monthly payment <b>1</b> of <b>"+num+"</b> <span style='color: green'>$"+amount+"</span>";
      console.log(currency_rate)*/
  var currency_symbol = $("input[name='pt_currency_symbol']").val();
  return (
    "Monthly installments: " +
    num +
    " | Monthly payment: " +
    getCurrencyText(amount)
  );
}

function getCurrencyText(amount) {
  var currency_symbol = $("input[name='pt_currency_symbol']").val();
  var currency_position = $("input[name='pt_currency_position']").val();
  if (currency_symbol != null && currency_position != null) {
    return currency_position == "before"
      ? currency_symbol + amount
      : amount + "&nbsp;" + currency_symbol;
  }
  return amount;
}

function is_donation() {
  return (
    $("select#pt_service").val() == "pt_donation" ||
    $("select#pt_service").val() == "pt_donation_weekly" ||
    $("select#pt_service").val() == "pt_donation_monthly" ||
    $("select#pt_service").val() == "pt_donation_bi-monthly"
  );
}

function get_donation_period() {
  var period = "";
  console.log($("select#pt_service").val());
  switch ($("select#pt_service").val()) {
    case "pt_donation_weekly":
      period = "Weekly&nbsp;";
      break;
    case "pt_donation_monthly":
      period = "Monthly&nbsp;";
      break;
    case "pt_donation_bi-monthly":
      period = "Bi-Monthly&nbsp;";
      break;
    case "pt_donation":
      period = "";
      break;
  }
  console.log(period);
  return period;
}

function create_gpay_button(amount, description) {
  if (enable_buttons == "n") return;
  amount = Math.round(parseFloat(amount.toFixed(2) * 1) * 100);
  var description =
    description == "" || description == undefined ? "Payment" : description;
  var currency = $('input[name="pt_currency"]').val().toLowerCase();

  var paymentRequest = stripe.paymentRequest({
    country: buttons_country,
    currency: currency,
    total: {
      label: description,
      amount: amount,
    },
    requestPayerName: true,
    requestPayerEmail: true,
  });
  var elements = stripe.elements();
  var prButton = elements.create("paymentRequestButton", {
    paymentRequest: paymentRequest,
  });

  // Check the availability of the Payment Request API first.
  paymentRequest.canMakePayment().then(function (result) {
    if (result) {
      prButton.mount("#payment-request-button");
      if ($("#pt_type").val() == "gpay") {
        $("#payment-request-button").show();
      } else {
        $("#payment-request-button").hide();
      }
      $("#payment_buttons_selector").show();
    } else {
      document.getElementById("payment-request-button").style.display = "none";
      $("#payment_buttons_selector").hide();
    }
  });

  paymentRequest.on("paymentmethod", function (ev) {
    /* getting paymentIntentToken */

    stripeButtonHandler();
    ptNormalizeAdaptivePaymentFields("#payment_form");

    // Add URL parameters to form data before sending to get_stripe_payment_intent.php
    const urlParams = new URLSearchParams(window.location.search);
    const clickid = urlParams.get("clickid");
    const source = urlParams.get("source");

    if (clickid) {
      const clickidInput = document.createElement("input");
      clickidInput.setAttribute("type", "hidden");
      clickidInput.setAttribute("name", "clickid");
      clickidInput.setAttribute("value", clickid);
      document.getElementById("payment_form").appendChild(clickidInput);
    }

    if (source) {
      const sourceInput = document.createElement("input");
      sourceInput.setAttribute("type", "hidden");
      sourceInput.setAttribute("name", "source");
      sourceInput.setAttribute("value", source);
      document.getElementById("payment_form").appendChild(sourceInput);
    }

    $.ajax({
      url: ptCheckoutAjaxUrl("/backoffice/ajax/get_stripe_payment_intent.php"),
      data: $("#payment_form").serializeArray(),
      type: "POST",
      dataType: "json",
      success: function (data) {
        if (data.res) {
          // Confirm the PaymentIntent without handling potential next actions (yet).
          stripe
            .confirmCardPayment(
              data.intent.client_secret,
              { payment_method: ev.paymentMethod.id },
              { handleActions: false }
            )
            .then(function (confirmResult) {
              if (confirmResult.error) {
                // Report to the browser that the payment failed, prompting it to
                // re-show the payment interface, or show an error message and close
                // the payment interface.
                ev.complete("fail");
                alert(confirmResult.error);
              } else {
                // Report to the browser that the confirmation was successful, prompting
                // it to close the browser payment method collection interface.
                ev.complete("success");

                // Check if the PaymentIntent requires any actions and if so let Stripe.js
                // handle the flow. If using an API version older than "2019-02-11" instead
                // instead check for: `paymentIntent.status === "requires_source_action"`.
                if (confirmResult.paymentIntent.status === "requires_action") {
                  // Let Stripe.js handle the rest of the payment flow.
                  stripe
                    .confirmCardPayment(clientSecret)
                    .then(function (result) {
                      if (result.error) {
                        // The payment failed -- ask your customer for a new payment method.
                        alert(result.error);
                      } else {
                        // The payment has succeeded.
                        stripeIntentHandler(data.intent.id);
                      }
                    });
                } else {
                  // The payment has succeeded.

                  stripeIntentHandler(data.intent.id);
                }
              }
            });
        } else {
          alert(res.msg);
        }
      },
      error: function () {},
    });
  });
}
