<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

require_once( __DIR__ . '/inc/autoload.php' );

$plugin = new \MightyDev\WordPress\Nooz;

$plugin->uninstall();
