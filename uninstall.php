<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    status_header( 404 );
    exit;
}

use MightyDev\WordPress\Plugin\NoozCore;

require_once( dirname( __FILE__ ) . '/inc/autoload.php' );
$nooz_core = new NoozCore();
$nooz_core->uninstall();
