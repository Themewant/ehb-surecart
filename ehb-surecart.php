<?php
/**
 * Plugin Name: EHB Surecart
 * Description: Easy Hotel Surecart Plugin, Datebase solution for easy hotel plugin.
 * Plugin URI:  https://themewant.com/downloads/hotel-booking/
 * Author:      Themewant
 * Author URI:  http://themewant.com/
 * Version:     1.0.4
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ehb-surecart
 * Domain Path: /languages
 * Requires Plugins: surecart
*/
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

define( 'ESHB_SURECART_VERSION', '1.0.4' );
define( 'ESHB_SURECART_PL_ROOT', __FILE__ );
define( 'ESHB_SURECART_PL_URL', plugins_url( '/', ESHB_SURECART_PL_ROOT ) );
define( 'ESHB_SURECART_PL_PATH', plugin_dir_path( ESHB_SURECART_PL_ROOT ) );
define( 'ESHB_SURECART_DIR_URL', plugin_dir_url( ESHB_SURECART_PL_ROOT ) );
define( 'ESHB_SURECART_PLUGIN_BASE', plugin_basename( ESHB_SURECART_PL_ROOT ) );
define( 'ESHB_SURECART_ASSETS_PATH', ESHB_SURECART_DIR_URL . 'assets' );

include 'activation.php';
include 'class.surecart.php';

register_activation_hook(__FILE__, 'eshb_surecart_update_plugin_options_for_activation');
register_deactivation_hook(__FILE__, 'eshb_surecart_update_plugin_options_for_deactivation');



$ESHB_SURECART = new ESHB_SURECART();

