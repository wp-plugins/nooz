<?php

/*
Plugin Name: Nooz
Plugin URI: http://www.mightydev.com/nooz/
Description: Simplified press release and media coverage management for business websites.
Author: Mighty Digital
Author URI: http://www.mightydev.com
Version: 0.6.0
*/

use \MightyDev\Registry;
use \MightyDev\Templating\TwigTemplating;
use MightyDev\WordPress\AdminHelper;
use \MightyDev\WordPress\Settings;
use \MightyDev\WordPress\Updater;
use \MightyDev\WordPress\Plugin\NoozCore;
use \MightyDev\WordPress\Plugin\NoozLicense;

add_action( 'plugins_loaded', 'mdnooz_core_load', 10 );
function mdnooz_core_load() {
    require_once( dirname( __FILE__ ) . '/inc/autoload.php' );
    $nooz_core = new NoozCore( __FILE__ );
    $nooz_core->title( 'Nooz' );
    $nooz_core->version( '0.6.0' );
    $nooz_core->set_admin_helper( new AdminHelper() );
    $nooz_core->set_settings( new Settings() );
    $array_loader = new Twig_Loader_Array( array() );
    $file_loader = new Twig_Loader_Filesystem( array( dirname( __FILE__ ) . '/inc/templates' ) );
    $chain_loader = new Twig_Loader_Chain( array( $array_loader, $file_loader ) );
    $twig = new Twig_Environment( $chain_loader, array( 'autoescape' => false ) );
    $nooz_core->set_templating( new TwigTemplating( $twig, $array_loader ) );
    $nooz_core->register();
    Registry::set( 'core', $nooz_core );
    Registry::set( 'file_loader', $file_loader );
    do_action( 'nooz_init' );
}
