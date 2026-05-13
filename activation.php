<?php
function eshb_surecart_update_plugin_options_for_activation() {

    update_option( 'eshb_surecart_activated', 'true' );

}

function eshb_surecart_update_plugin_options_for_deactivation() {

    delete_option( 'eshb_surecart_activated' );

}
