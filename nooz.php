<?php

/*
Plugin Name: Nooz
Plugin URI: http://mightydev.com/nooz/
Description: Simplified press release and media coverage management for business websites.
Author: Mighty Digital
Author URI: http://mightydigital.com
Version: 0.5.2
*/

require_once __DIR__ . '/inc/bootstrap.php';

use \MightyDev\WordPress\Plugin\NoozCore;
use \MightyDev\Templating\TwigTemplating;
use \MightyDev\WordPress\Settings;

$nooz_core = new NoozCore();
$nooz_core->set_plugin_file( __FILE__ );
$nooz_core->title( 'Nooz' );
$nooz_core->version( '0.5.2' );
$nooz_core->wpalchemy_factory( new \WPAlchemy\Factory );
$array_loader = new Twig_Loader_Array( array() );
$file_loader = new Twig_Loader_Filesystem( array( __DIR__ . '/inc', __DIR__ . '/inc/templates' ) );
$chain_loader = new Twig_Loader_Chain( array( $array_loader, $file_loader ) );
$twig = new Twig_Environment( $chain_loader, array( 'autoescape' => false ) );
$templating = new TwigTemplating( $twig, $array_loader );
$nooz_core->set_templating( $templating );
$settings = new Settings();
$nooz_core->set_settings($settings);
add_action( 'plugins_loaded', array ( $nooz_core, 'register' ) );

if ( file_exists( __DIR__ . '/pro/pro.php' ) && !class_exists( '\NoozPro' ) ) {
    require_once( __DIR__ . '/pro/pro.php' );
}
