"use strict";
$().ready(function() {
    // validate the comment form when it is submitted
    $(".validate").validate({

        errorPlacement: function(error, element) {

            element.parents(".form-group").addClass("has-error");
            element.wrap("<div class='control-wrap'>");
            error.appendTo(element.parent());

        },
        success: function(label) {
            label.parents(".form-group").removeClass("has-error").addClass("has-success");
        }
    });

    if(typeof wysihtml5 === "object"){
        $('#email_signature').wysihtml5();
        $('#thank_you_message').wysihtml5();
        $('#terms_and_conditions').wysihtml5();
    }

    $("[name='use_recaptcha']").change(function(){
        var $el = $(this);
        if($el.val()==='y'){
            $('#recapcha').show();
        }else{
            $('#recapcha').hide();
        }
    });

    $("[name='show_terms']").change(function(){
        var $el = $(this);
        if($el.val()==='y'){
            $('#terms_and_conditions_cont').show();
        }else{
            $('#terms_and_conditions_cont').hide();
        }
    });

    $("[name='custom_action']").change(function(){
        var $el = $(this);
        if($el.val()==='y'){
            $('#custom_action_cont').show();
        }else{
            $('#custom_action_cont').hide();
        }
    });

    $("[name='attach_pdf_invoice']").change(function(){
        var $el = $(this);
        if($el.val()==='y'){
            $('#display_pdf_payment_options_cont').show();
        }else{
            $('#display_pdf_payment_options_cont').hide();
        }
    });

    $("[name='terminal_payment_mode']").change(function(){
        var $el = $(this);
        if($el.val()==='live') {
            $('.live-req').removeClass('hide');
            $('.test-req').addClass('hide');
            $('#live_secret_key').data('rule-required', true);
            $('#live_public_key').data('rule-required', true);
            $('#test_secret_key').data('rule-required', false);
            $('#test_public_key').data('rule-required', false);
            $('#live_secret_key').parent().parent().removeClass('has-error');
            $('#live_public_key').parent().parent().removeClass('has-error');
            $('#test_secret_key').parent().parent().removeClass('has-error');
            $('#test_public_key').parent().parent().removeClass('has-error');
            $('label.error').remove();
        } else {
            $('.test-req').removeClass('hide');
            $('.live-req').addClass('hide');
            $('#live_secret_key').data('rule-required', false);
            $('#live_public_key').data('rule-required', false);
            $('#test_secret_key').data('rule-required', true);
            $('#test_public_key').data('rule-required', true);
            $('#live_secret_key').parent().parent().removeClass('has-error');
            $('#live_public_key').parent().parent().removeClass('has-error');
            $('#test_secret_key').parent().parent().removeClass('has-error');
            $('#test_public_key').parent().parent().removeClass('has-error');
            $('label.error').remove();
        }
    });

    $("#send_mail").change(function () {
        if($(this).val()=='php'){
            $("#smtp_cont").hide()
        }else{
            $("#smtp_cont").show()
        }
    })

    if($('.colorpick').length)
    $('.colorpick').colorpicker();

    $(".btn-group[data-toggle='buttons'] > label").click(function(){
        var $input =$(this).find("input");
        $input.prop("checked", true);

    });

    $("input[name='theme_type']").change(function(){
        if($(this).val()==='theme'){
            $("#theme_settings").show();
            $("#custom_settings").hide();
            // update preview when switching back to theme mode
            updateThemePreview();
        }else{
            $("#theme_settings").hide();
            $("#custom_settings").show();
        }
    });

    // Theme preview handling
    function updateThemePreview(){
        var $sel = $("select[name='selected_theme']");
        var $img = $("#theme_preview");
        var $path = $("#theme_preview_path");
        if(!$sel.length || !$img.length) return;
        
        // Default to CardStyle if no valid theme is selected
        var theme = ($sel.val() || '').trim();
        if(theme !== 'CardStyle' && theme !== 'Minimalist' && theme !== 'Colorful' && theme !== 'adaptive-lp') { 
            theme = 'CardStyle';
            $sel.val(theme); // Update the select element to show the correct theme
        }

        // Only use assets/images path per requirement
        var baseImages = $img.data('preview-base') || ''; // site_url + '/assets/images/'
        var defaultFile = 'criticalgears.png';

        // List of possible image locations to try
        var candidates = [
            { url: baseImages + 'themes/' + theme.toLowerCase() + '.png', label: '/assets/images/themes/' + theme.toLowerCase() + '.png' },
            { url: baseImages + theme.toLowerCase() + '.png', label: '/assets/images/' + theme.toLowerCase() + '.png' },
            { url: baseImages + 'cardstyle.png', label: '/assets/images/cardstyle.png' },
            { url: baseImages + defaultFile, label: '/assets/images/' + defaultFile }
        ];

        function setPreview(src, label) {
            $img.attr('src', src);
            if($path && $path.length) { 
                $path.text(label); 
            }
        }

        function tryNext(i) {
            if(i >= candidates.length) { 
                console.warn('No valid theme preview image found');
                return; 
            }
            var c = candidates[i];
            var probe = new Image();
            probe.onload = function() { 
                setPreview(c.url, c.label); 
            };
            probe.onerror = function(){ tryNext(i+1); };
            // bust cache for first candidate
            var src = c.url;
            if(i === 0){ src += (src.indexOf('?') === -1 ? '?' : '&') + 'v=' + Date.now(); }
            probe.src = src;
        }

        tryNext(0);
    }

    // React to dropdown changes
    $("select[name='selected_theme']").on('change', function(){
        updateThemePreview();
    });

    // initialize on load
    updateThemePreview();

    $("input[name='multiple_currencies']").change(function(){
        if($(this).val()==='y'){
            $("#multiple_currency").show();
            $("#single_currency").hide();
        }else{
            $("#multiple_currency").hide();
            $("#single_currency").show();
        }
    });
    $("input[name='multiple_currency_selector']").change(function(){
        if($(this).val()==='y'){
            $("#multiple_currency_list").show();

        }else{
            $("#multiple_currency_list").hide();

        }
    });


    $("input[name='paypal_currency_converter']").change(function(){
        if($(this).val()==='y'){
            $("#convert").show();

        }else{
            $("#convert").hide();

        }
    });

    $("select[name='paypal_currency_converter_api']").change(function(){
        if($(this).val()==='fixer'){
            $("#api_key").hide();

        }else{
            $("#api_key").show();

        }
    });

    $("input[name='tax_enable']").change(function(){
        if($(this).val()==='y'){
            $("#tax_options").show();
        }else{
            $("#tax_options").hide();
        }
    });

    $("input[name='fee_enable']").change(function(){
        if($(this).val()==='y'){
            $("#fee_options").show();
        }else{
            $("#fee_options").hide();
        }
    });

    $("input[name='buttons_enable']").change(function(){
        if($(this).val()==='y'){
            $("#country_cont").show();
        }else{
            $("#country_cont").hide();
        }
    });
    $('.deactivate').on('click',function () {
        var $el = $(this)
        swal({
            title: 'Deactivate Plugin',
            text: 'Are you sure you would like to deactivate this plugin?',
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: '#DD6B55',
            confirmButtonText: 'Proceed',
            cancelButtonText: "Cancel"

        }).then(function (result) {
            window.location.href=$el.attr("href");
        });
        return false;
    })
});

function check_license(plugin) {
    $("#checkLicense").modal('show');
    $("#form_plugin").val(plugin)
}
