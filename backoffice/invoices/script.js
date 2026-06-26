var totals = 0;

$().ready(function () {
    "use strict";
    $('#invoiceDueDate,#invoiceDate').datepicker({
        format: "mm/dd/yyyy"
    });
    // validate the comment form when it is submitted
    $("#invoiceForm").validate({

        errorPlacement: function (error, element) {

            element.parents(".form-group").addClass("has-error");
            element.wrap("<div class='control-wrap'>");
            error.appendTo(element.parent());

        },
        success: function (label) {
            label.parents(".form-group").removeClass("has-error").addClass("has-success");
        }
    });

    $("input[name='itemType']").on("click", function () {
        var $el = $(this);
        if ($el.val() === 'product') {
            $("#itemFrequency").attr("disabled", "disabled").prev("label").addClass("disabled");
        } else {
            $("#itemFrequency").removeAttr("disabled").prev("label").removeClass("disabled");
        }
    });

    $("select[name='invoiceCurrency']").on("change", function () {
        var $el = $(this);
        var cur = $(this).find("option:selected").data("symbol");
        $(".invoiceCurrency").html(cur);
    });

    var template = jQuery.validator.format($.trim($("#template").html()));

    var iterator = 1;

    $("#addItem").on("click", function () {
        $(template(iterator++)).appendTo("#itemsCont");
        iterator++;
        $('.typeahead').typeahead('destroy');

        fetchTypeahead();
    });

    $("#invoiceLateFee").on("blur",function(){
        var terms_text = $("#invoiceTerms").val();
       if(parseInt($(this).val())>0){
            console.log(terms_text.indexOf("Late fee in the amount of"));
           if(terms_text.indexOf("Late fee in the amount of")!=-1) {
               var text = terms_text.replace(/([0-9]+)%/g, $(this).val()+"%");
               $("#invoiceTerms").val(text);
           }else{
               var text = lateFeeText.replace(/\{1\}/g, $(this).val());
               $("#invoiceTerms").val(terms_text + (terms_text !== '' ? "\n" + text : text));
           }
       }
    });

    $("#itemsCont").on("keyup",".itemQty,.itemRate,.itemDiscount,.itemTax",updateTotals);
    $('input[name="invoiceLateFee"]').on("keyup", updateTotals);

    /*$("#newItemCont").validateDelegate("input.itemRate,input.itemQty,input.itemTax,input.itemDiscount", "keyup", function (event) {

        updateTotals();

    });*/

    $("#invoiceDate").change(function () {
        $("#invoiceTerm").trigger("change");
    });
    $("#invoiceTerm").change(function () {
        var $el = $(this);
        var now = new Date($("#invoiceDate").val());
        var targetInput = $("#invoiceDueDate");
        var targetText = $("#orderDueText");

        var textCont = $("#dueDateText");
        var controlCont = $("#dueDateControl");
        if(isNaN(now)){
            textCont.hide();
            return;
        }


        if ($el.val() === '0') {

            textCont.show();
            controlCont.hide();
            targetText.html(now.format("MMM DD, YYYY"));
            targetInput.val(now.format("MM/DD/YYYY"));
        } else if ($el.val() === 'custom') {
            textCont.hide();
            controlCont.show();

        } else {

            var days = parseInt($el.find("option:selected").data("days"));
            var tomorrow = new Date(now);
            tomorrow.setDate(now.getDate() + days);
            textCont.show();
            controlCont.hide();
            targetText.html(tomorrow.format("MMM DD, YYYY"));
            targetInput.val(tomorrow.format("MM/DD/YYYY"));
        }
    });

    updateTotals();

    fetchTypeahead();
    fetchTypeaheadCustomer();
});

var removeRow = function (el){
    $(el).parents('.blue_section').remove();
    updateTotals();
}

var deleteRow = function (el,idItem){

        $.getJSON("removeItem.php",{idItem:idItem},function(d){
            if(d.res){
                $(el).parents('.blue_section').fadeOut('fast',function(){$(this).remove();updateTotals();});

            }
        });

};

var updateTotals = function () {
    "use strict";
    var total = 0;
    var subtotal = 0;
    var totalTax = 0;
    var lateFee = 0;
    var totalDiscount = 0;

    var invoiceSubtotalCont = $("#invoiceSubTotal");
    var invoiceTotalCont = $("#invoiceTotal");
    var invoiceTaxCont = $("#invoiceTax");
    var invoiceDiscountCont = $("#invoiceDiscount");
    var invoiceLateFee = $("#invoiceTotalLateFee");

    $("#itemsCont").find("div.blue_section").each(function () {
        var $row = $(this);

        var itemSubtotal;
        var itemTotal;
        var itemDiscount;
        var itemTax;
        var subtotalCont = $row.find(".txt-amount > span");

        var itemQty = $row.find("input[name*='itemQty']").val();

        itemQty = isNaN(itemQty) || itemQty === '' ? 0 : parseInt(itemQty);

        var itemRate = $row.find("input[name*='itemRate']").val();
        itemRate = isNaN(itemRate) || itemRate === '' ? 0 : parseFloat(itemRate);

        itemDiscount = $row.find("input[name*='itemDiscount']").val();
        itemDiscount = isNaN(itemDiscount) || itemDiscount === '' ? 0 : itemDiscount;

        itemTax = $row.find("input[name*='itemTax']").val();
        itemTax = isNaN(itemTax) || itemTax === '' ? 0 : itemTax;

        itemTotal = (itemRate * itemQty).round(2);

        var itemDiscountValue = (itemTotal * itemDiscount / 100).round(2);
        itemSubtotal = itemTotal - itemDiscountValue;

        var itemTaxValue = (itemSubtotal * itemTax / 100).round(2);

        subtotalCont.html((itemSubtotal+itemTaxValue).toFixed(2));

        total += itemTotal;
        //subtotal +=itemSubtotal;
        subtotal +=itemTotal;
        totalTax += itemTaxValue;
        totalDiscount += itemDiscountValue;
    });

    var invoiceLateFee_v = $('#invoiceLateFee').val();
    if( invoiceLateFee_v == '' ){
        invoiceLateFee_v = 0;
    }
    lateFee = ( (subtotal-totalDiscount ) * ( parseInt(invoiceLateFee_v) / 100 ) ).round(2);

    invoiceSubtotalCont.html(subtotal.toFixed(2));
    invoiceLateFee.html(lateFee.toFixed(2));
    invoiceTotalCont.html((total - totalDiscount + totalTax + lateFee ).toFixed(2));

    if(totalDiscount > 0){
        invoiceDiscountCont.html(totalDiscount.toFixed(2));
        $("#invoiceDiscountRow").show();
    }else{
        invoiceDiscountCont.html(0);
        $("#invoiceDiscountRow").hide();
    }

    if(totalTax > 0){
        invoiceTaxCont.html(totalTax.toFixed(2));
        $("#invoiceTaxRow").show();
        /*$("#invoiceTotalRow .invoiceSubtotals").removeClass("odd");
        $("#invoiceTaxRow .invoiceSubtotals").addClass("odd");*/
    }else{
        invoiceTaxCont.html(0);
        $("#invoiceTaxRow").hide();
        /*$("#invoiceTotalRow .invoiceSubtotals").addClass("odd");*/
    }
    if(invoiceLateFee_v > 0){
        $("#invoiceFeeRow").show();
    }else{
        $("#invoiceFeeRow").hide();
    }
    if(totalTax <= 0 && invoiceLateFee_v == 0){
        /*$("#invoiceTotalRow .invoiceSubtotals").removeClass("odd");*/
    }
    if(totalTax > 0 && invoiceLateFee_v == 0){
        /*$("#invoiceTaxRow:last-of-type .invoiceSubtotals").removeClass("odd");*/
    }
    $(".total-items-rows .form_section .row:first-child").removeClass('odd');
    $(".total-items-rows .form_section:visible:odd .row:first-child").addClass('odd');

};

var fetchTypeahead = function () {
    "use strict";
    $('.typeahead').typeahead({
            hint: true,
            highlight: true,
            minLength: 1
        },
        {
            name: 'states',
            displayKey: function (e) {

                return e.value.label;
            },
            source: substringMatcher(items),
            templates: {
                empty: [
                    '<div class="empty-message">',
                    '<p>This item will be saved as new</p>',
                    '</div>'
                ].join('\n'),
                suggestion: function (e, b) {
                    var currencyPos = $("input[name='invoiceCurrencyPosition']").val();
                    var currencySymbol = $("input[name='invoiceCurrencySymbol']").val();
                    var rateText = (currencyPos==='before'?currencySymbol:"")+e.value.rate+(currencyPos==='after'?currencySymbol:"");
                    return '<p><strong>' + e.value.label + '</strong> – ' + rateText + '</p>';
                }
            }
        });
    $('.typeahead').bind('typeahead:selected', prefillProduct);
    $('.typeahead').bind('typeahead:autocompleted', prefillProduct);
    $('.typeahead').on('keyup', function (e) {
    //console.log(e.keyCode);
        if (e.keyCode !== 13 && e.keyCode !== 32 && e.keyCode !== 37 && e.keyCode !== 38 && e.keyCode !== 39 && e.keyCode !== 40) {
            var rateInput = $(e.currentTarget).parents(".blue_section").find("input[name^='itemRate']");
            var itemInput = $(e.currentTarget).parents(".blue_section").find("input[name^='itemItem']");
            rateInput.val('');
            itemInput.val('');
            updateTotals();
        }
    });

};

var prefillProduct = function (obj, datum, name) {
    "use strict";
    var rateInput = $(obj.currentTarget).parents(".blue_section").find("input[name^='itemRate']");
    var itemInput = $(obj.currentTarget).parents(".blue_section").find("input[name^='itemItem']");
    rateInput.val(datum.value.rate);
    itemInput.val(datum.value.id);
    updateTotals();
};


var fetchTypeaheadCustomer = function () {
    "use strict";
    $('.typeaheadCustomer').typeahead({
            hint: true,
            highlight: true,
            minLength: 1
        },
        {
            name: 'states',
            displayKey: function (e) {

                return e.value.label;
            },
            source: substringMatcher(customers),
            templates: {
                empty: [
                    '<div class="empty-message">',
                    '<p>This customer will be saved as new</p>',
                    '</div>'
                ].join('\n'),
                suggestion: function (e, b) {

                    return '<p><strong>' + e.value.label + '</strong></p>';
                }
            }
        });
    $('.typeaheadCustomer').bind('typeahead:selected', prefillCustomer);
    $('.typeaheadCustomer').bind('typeahead:autocompleted', prefillCustomer);

};

var prefillCustomer = function (obj, datum, name) {
    "use strict";

    for(var i in datum.value){
        $("#"+i).val(datum.value[i]);
    }
};

var substringMatcher = function (strs) {
    "use strict";
    return function findMatches(q, cb) {
        var matches, substrRegex;

        // an array that will be populated with substring matches
        matches = [];

        // regex used to determine if a string contains the substring `q`
        substrRegex = new RegExp(q, 'i');

        // iterate through the pool of strings and for any string that
        // contains the substring `q`, add it to the `matches` array
        $.each(strs, function (i, str) {
            if (substrRegex.test(str.label)) {
                // the typeahead jQuery plugin expects suggestions to a
                // JavaScript object, refer to typeahead docs for more info
                matches.push({ value: str });
            }
        });

        cb(matches);
    };
};

var addNewItem = function (el) {
    "use strict";
    var $el = $(el);
};

var confirmSave = function(type){

    if (type == 'send') {
        console.log(type)
        var input = document.createElement( "input" );
        input.id = 'send_action';
        input.name = 'sendInvoice';
        input.type = 'hidden';
        $('#invoiceForm').append(input);
    } else {
        $('#send_action').remove();
    }
    $("#confirmEdit").modal('show')
}

Number.prototype.round = function(places){
    places = Math.pow(10, places);
    return Math.round(this * places)/places;
};
