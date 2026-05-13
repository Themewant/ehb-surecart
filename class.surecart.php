<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.
define( 'ESHB_SURECART_ID', 9750 );

class ESHB_SURECART {
    private $eshb_surecart_settings;
    public function __construct()
    {   
        $this->eshb_surecart_settings = get_option('eshb_surecart_settings', []);
        $this->includes();
        add_action('init', array($this, 'init'));
        add_action('admin_init', array($this, 'version_updater_notice'));
        add_action('admin_enqueue_scripts', [$this, 'eshb_admin_enqueue_scripts']);
    }
    
    public function init()
    {
        // Add Plugin actions
		add_filter( 'plugin_action_links_' . ESHB_SURECART_PLUGIN_BASE, [ $this, 'plugin_action_links' ], 10, 4 );

    }

	public function plugin_action_links( $plugin_actions, $plugin_file, $plugin_data, $context ) {

		$new_actions = array();
		$new_actions['easy_hotel_surecart_plugin_actions_setting'] = '<a href="'.admin_url( 'edit.php?post_type=eshb_accomodation&page=easy-hotel-surecart-settings' ).'">'. esc_html__( 'Settings', 'ehb-min-max' ) .'</a>';

		return array_merge( $new_actions, $plugin_actions );

	}

    public function eshb_admin_enqueue_scripts (){
    
        wp_enqueue_script( 'ehb-surecart-admin', ESHB_SURECART_PL_URL . 'assets/js/admin.js', array('jquery'), ESHB_SURECART_VERSION, true );

        wp_localize_script( 'ehb-surecart', 'eshb_surecart_obj', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'eshb_surecart_nonce' ),
            'site_url' => site_url(),
            'ics_rest_url' => site_url() .'/wp-json/eshb/v1/ics/?accomodation_id=',
            'import_nonce' => wp_create_nonce( 'eshb_surecart_import_nonce' ),
            'importer_message' => array(
                'fields_error' => esc_html__( 'All fields are required.', 'ehb-surecart' ),
            )
        ) );
    }

    public function includes(){

        require_once ESHB_SURECART_PL_PATH . 'includes/class.admin-settings.php';
		require_once ESHB_SURECART_PL_PATH . 'includes/class.helper.php';
		require_once ESHB_SURECART_PL_PATH . 'includes/class.admin-ajax-request.php';
		require_once ESHB_SURECART_PL_PATH . 'includes/class.booking.php';
		require_once ESHB_SURECART_PL_PATH . 'includes/class.coupon.php';
		require_once ESHB_SURECART_PL_PATH . 'includes/class.currency.php';
		require_once ESHB_SURECART_PL_PATH . 'includes/class.filters.php';

        if(is_admin() && !class_exists( 'EDD_SL_Plugin_Updater' )){
			require_once ESHB_SURECART_PL_PATH . 'includes/EDD_SL_Plugin_Updater.php';
		}
    }
    
    public function check_license($license_key = null) {
		// Retrieve the license key from options if not provided
		$license_key = $license_key ?? get_option('eshb_surecart_license_key', '');
	
		// If no license key is available, return 'inactive'
		if (empty($license_key)) {
			return 'inactive';
		}
	
		$response = wp_remote_post('https://account.themewant.com', array(
			'body' => array(
				'edd_action' => 'check_license',
				'item_id' => ESHB_SURECART_ID,
				'license' => $license_key,
				'url' => site_url()
			)
		));
	
		if (is_wp_error($response)) {
			// Return 'inactive' on error
			return 'inactive';
		}
	
		$apiBody = json_decode(wp_remote_retrieve_body($response));
	
		// Check the license status from the response
		if ($apiBody->success === true) {
			if ($apiBody->license === 'valid') {
				return 'active';
			} elseif ($apiBody->license === 'expired') {
				return 'expired';
			}
		}
	
		return 'inactive';
	}
	
	public function activate_license($license_key){
		$response = wp_remote_post( 'https://account.themewant.com', array(
			'body' => array(
				'edd_action' => 'activate_license',
				'item_id' => ESHB_SURECART_ID,
				'license' => $license_key,
				'url'     => site_url()
			)
		) );
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			echo esc_html($error_message);
		} else {
			$apiBody = json_decode( wp_remote_retrieve_body( $response ) );
			return $apiBody;
		}
		
	}

	public function deactivate_license(){
		$license_key = '';
		if ( get_option( 'eshb_surecart_license_key' ) !== false ) {
			$license_key = get_option( 'eshb_surecart_license_key' );
		} 
		$response = wp_remote_post( 'https://account.themewant.com', array(
			'body' => array(
				'edd_action' => 'deactivate_license',
				'item_id' => ESHB_SURECART_ID,
				'license' => $license_key,
				'url'     => site_url()
			)
		) );
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			echo esc_html($error_message);
		} else {
			$apiBody = json_decode( wp_remote_retrieve_body( $response ) );
			return $apiBody;
		}
		
	}

	public function version_updater_notice(){
		// retrieve our license key from the DB
		$license_key = get_option('eshb_surecart_license_key', '');
		
		// The site for the EDD store.
		$apiUrl         = 'https://account.themewant.com';

		$pluginFile     = 'ehb-surecart/ehb-surecart.php';
		// The version of your add-on/plugin.
		$currentVersion = ESHB_SURECART_VERSION;
		$author         = 'Themewant';

		if(!empty($license_key)){
			// setup the updater
			$edd_updater = new ESHB_SURECART\EDD_SL_Plugin_Updater( $apiUrl, $pluginFile, array(
				'version' 	=> $currentVersion,		// current version number
				'license' 	=> $license_key,	// license key (used get_option above to retrieve from DB)
				'item_id'   => ESHB_SURECART_ID,	// id of this plugin
				'author' 	=> $author,	// author of this plugin
				'beta'      => false                // set to true if you wish customers to receive update notifications of beta releases
			) );
		}
		
	}

}


   
