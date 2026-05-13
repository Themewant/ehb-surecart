(function($) {

    let ESHBDBADMIN = {
        init: function () { 
            $( document )
            .on( 'click.ESHBDBADMIN', '.easy-hotel-surecart-settings .eshb-activate-license', this.activateESHBLicense )
            .on( 'click.ESHBDBADMIN', '.easy-hotel-surecart-settings .eshb-deactivate-license', this.deactivateESHBLicense )
            ;
        },
        showModalAjaxLoader: function () { 
            $('#eshb-menu-setting-modal .ajax-loader, #eshb-license-activator .ajax-loader').css('display', 'flex');
        },
        hideModalAjaxLoader: function () {
            $('#eshb-menu-setting-modal .ajax-loader, #eshb-license-activator .ajax-loader').css('display', 'none');
        },
        activateESHBLicense: function () { 
    
            
            let licenseKey = $('#eshb-license-activator input[name="licnese_key"]').val();
            let formStatusEL = $('.form-status');
        
            formStatusEL.html('');

            if(licenseKey == '') {
                formStatusEL.html('<span class="eshb-text-danger">Enter license key first!</span>');
                return;
            }


            ESHBDBADMIN.showModalAjaxLoader($(this));
            $.ajax({
                type: 'POST',
                url: eshb_surecart_obj.ajax_url,
                data: {
                    action        : "eshb_surecart_activate_license",
                    licenseKey    : licenseKey,
                    nonce : eshb_surecart_obj.nonce,
                },
                cache: false,
                success: function(response) {
                    console.log(response);
                    if(response){
                        if(response == 'success'){
                            formStatusEL.html('<span class="eshb-text-success">License activated!</span>');
                        }else if(response == 'already_activated'){
                            formStatusEL.html('<span class="eshb-text-danger">License already activated!</span>');
                        }else{
                            formStatusEL.html('<span class="eshb-text-danger">License can\'t be activated. Please contact to plugin support center to fix this problem.</span>');
                        }
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                        ESHBDBADMIN.hideModalAjaxLoader($(this));
                    }
                    
                }
            });


        },
        deactivateESHBLicense: function () { 

            ESHBDBADMIN.showModalAjaxLoader($(this));
            let licenseKey = $('#eshb-license-activator input[name="licnese_key"]').val();
            let formStatusEL = $('.form-status');
        
            formStatusEL.html('');

            $.ajax({
                type: 'POST',
                url: eshb_surecart_obj.ajax_url,
                data: {
                    action        : "eshb_surecart_deactivate_license",
                    licenseKey    : licenseKey,
                    nonce : eshb_surecart_obj.nonce,
                },
                cache: false,
                success: function(response) {
                    console.log(response);
                    if(response){
                        if(response == 'success'){
                            formStatusEL.html('<span class="eshb-text-danger">License deactivated!</span>');
                            setTimeout(() => {
                                location.reload();
                            }, 1000);
                        }else if(response == 'expired'){
                            formStatusEL.html('<span class="eshb-text-danger">License expired! License can\'t be deactivated. Please renew your license.</span>');
                        }else{
                            formStatusEL.html('<span class="eshb-text-danger">License can\'t be deactivated. Please contact to plugin support center to fix this problem.</span>');
                        }
                        
                        ESHBDBADMIN.hideModalAjaxLoader($(this));
                    }
                    
                }
            });
        },
    }

    ESHBDBADMIN.init();

})(jQuery);