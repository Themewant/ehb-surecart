<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if( class_exists( 'ESHB' ) ) {

    function ESHB_SURECART_license_callback(){
        ?>
          <div id="eshb-license-activator" class="eshb-license-activator">
                <?php 
                    $license_key = '';
                    if ( get_option( 'eshb_surecart_license_key' ) !== false ) {
                        $license_key = get_option( 'eshb_surecart_license_key' );
                    } 
    
                    
    
                    $ESHB_SURECART = new ESHB_SURECART();
                    $license_status = $ESHB_SURECART->check_license();
    
    
                    if( $license_status == 'inactive' ){
                        ?>
                            <div class="form-group">
                                <input type="password" name="licnese_key" id="eshb_licnese_key" placeholder="Enter license key">
                                <button type="button" class="button button-primary eshb-activate-license" id="eshb-activate-license">Activate</button>
                                <div class="ajax-loader">
                                  <img src="<?php echo esc_url(ESHB_SURECART_PL_URL.'assets/img/ajax-loader.gif'); ?>" alt="Ajax Loader">
                                </div>
                            </div>
                        <?php
                    }else{
    
                        ?>
                            <div class="form-group">
                                <input type="password" name="licnese_key" id="eshb-deactivate-license" placeholder="Enter license key" value="<?php echo esc_attr($license_key); ?>" disabled>
                                <button type="button" class="button button-secondary eshb-deactivate-license" id="eshb-deactivate-license">Deactivate</button>
                                <div class="ajax-loader">
                                  <img src="<?php echo esc_url(ESHB_SURECART_PL_URL.'assets/img/ajax-loader.gif'); ?>" alt="Ajax Loader">
                                </div>
                            </div>
    
                        <?php
                    }
                ?>
                <p class="form-status"><?php echo $license_status == 'expired' ? '<span class="eshb-text-danger">License expired!</span>' : ''; ?></p>
            </div>
        <?php
    }
    
    // Set a unique slug-like ID
    $prefix = 'eshb_surecart_settings';
     // Create options
    ESHB::createOptions( $prefix, array(
    
        // framework title
        'framework_title'         => 'Easy Hotel Surecart Settings',
        'framework_class'         => 'easy-hotel-surecart-settings easy-hotel-plugin-settings',
    
        // menu settings
        'menu_title'              => 'Easy Hotel Surecart Settings',
        'menu_slug'               => 'easy-hotel-surecart-settings',
        'menu_type'               => 'submenu',
        'menu_capability'         => 'manage_options',
        'menu_icon'               => 'dashicons-schedule',
        'menu_position'           => 4,
        'menu_hidden'             => false,
        'menu_parent'             => 'edit.php?post_type=eshb_accomodation',
    
        // menu extras
        'show_bar_menu'           => false,
        'show_sub_menu'           => false,
        'show_in_network'         => true,
        'show_in_customizer'      => false,
    
        'show_search'             => false,
        'show_reset_all'          => true,
        'show_reset_section'      => false,
        'show_footer'             => false,
        'show_all_options'        => false,
        'show_form_warning'       => false,
        'sticky_header'           => true,
        'save_defaults'           => true,
        'ajax_save'               => true,
    
        // database model
        'transient_time'          => 0,
    
        // typography options
        'enqueue_webfont'         => false,
        'aexport_webfont'           => false,
    
        // others
        'output_css'              => false,
    
        // theme and wrapper classname
        'nav'                     => 'inline',
        'theme'                   => 'light',
        'class'                   => '',
    
        // external default values
        'defaults'                => array(),
    
      )
    );
    ESHB::createSection( $prefix, array(
        'title'  => 'License', 'ehb-surecart',
        'fields' => array(
            array(
                'type'     => 'callback',
                'function' => 'eshb_surecart_license_callback',
                'title'    => 'Activate plugin license from here.',
                'class' => 'full-width-field-wrapper',
            ),
          )
        )
    );
}