<?php

/*
Plugin Name: Nooz
Plugin URI: http://mightydev.com/nooz/
Description: Simplified press release and media coverage management for business websites.
Author: Mighty Digital
Author URI: http://mightydigital.com
Version: 0.5.5
*/

use \MightyDev\Registry;
use \MightyDev\Templating\TwigTemplating;
use \MightyDev\WordPress\Settings;
use \MightyDev\WordPress\Updater;
use \MightyDev\WordPress\Plugin\NoozCore;
use \MightyDev\WordPress\Plugin\NoozLicense;

add_action( 'plugins_loaded', 'mdnooz_load_core', 10 );
function mdnooz_load_core() {
    require_once( __DIR__ . '/inc/autoload.php' );
    $nooz_core = new NoozCore( __FILE__ );
    $nooz_core->title( 'Nooz' );
    $nooz_core->version( '0.5.5' );
    $nooz_core->wpalchemy_factory( new \WPAlchemy\Factory );
    $nooz_core->set_settings( new Settings() );
    $array_loader = new Twig_Loader_Array( array() );
    $file_loader = new Twig_Loader_Filesystem( array( __DIR__ . '/inc/templates' ) );
    $chain_loader = new Twig_Loader_Chain( array( $array_loader, $file_loader ) );
    $twig = new Twig_Environment( $chain_loader, array( 'autoescape' => false ) );
    $nooz_core->set_templating( new TwigTemplating( $twig, $array_loader ) );
    $nooz_core->register();
    Registry::set( 'core', $nooz_core );
    Registry::set( 'file_loader', $file_loader );
    do_action( 'mdnooz_core_plugin_loaded' );
}
