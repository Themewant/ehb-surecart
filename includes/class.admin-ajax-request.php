<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.
if ( !class_exists('ESHB_SURECART_Admin_Ajax')) {
    class ESHB_SURECART_ADMIN_AJAX {

        function __construct(){

            add_action( "wp_ajax_eshb_surecart_activate_license", array ( $this, 'eshb_activate_license' ) );
            add_action( "wp_ajax_eshb_surecart_deactivate_license", array ( $this, 'eshb_deactivate_license' ) );
           
        }
    
        public function eshb_activate_license() {

            check_ajax_referer( 'eshb_surecart_nonce', 'nonce' );
            
            if (!empty($_POST['licenseKey'])) {
    
                $license_key = sanitize_text_field( wp_unslash( $_POST['licenseKey'] ));
                $ESHB_SURECART   = new ESHB_SURECART();
                
                $license_status = $ESHB_SURECART->check_license($license_key);
            
                if ($license_status === 'inactive') {
            
                    $activation = $ESHB_SURECART->activate_license($license_key);
            
                    if (!empty($activation->license) && $activation->license === 'valid' && !empty($activation->success) && $activation->success === true) {
                        
                        update_option('eshb_surecart_license_key', $license_key, 'no');
            
                        echo esc_html__('success', 'ehb-surecart');
                    } else {
                        echo esc_html__('invalid_license', 'ehb-surecart');
                    }
            
                } else {
                    echo esc_html__('already_activated', 'ehb-surecart');
                }
            }
            
            wp_die();
            

        }

        public function eshb_deactivate_license() {

            check_ajax_referer( 'eshb_surecart_nonce', 'nonce' );
            
            if (!empty($_POST['licenseKey'])) {
                $ESHB_SURECART = new ESHB_SURECART();
                $license_status = $ESHB_SURECART->check_license();
                
                if ($license_status !== 'inactive' && $license_status !== 'expired') {
                    $deactivation = $ESHB_SURECART->deactivate_license();
            
                    if ($deactivation->license === 'deactivated' && $deactivation->success === true) {
                        $license_option = get_option('eshb_surecart_license_key');
                        if ($license_option !== false) {
                            delete_option('eshb_surecart_license_key');
                        }
                        echo esc_html__('success', 'ehb-surecart');
                    }
                } elseif ($license_status === 'expired') {
                    echo esc_html__('expired', 'ehb-surecart');
                } else {
                    echo esc_html($license_status);
                }
            }
            
            wp_die();

        }

        

    }
    
    $ESHB_SURECART_ADMIN_AJAX = new ESHB_SURECART_ADMIN_AJAX();
}