<?php

/*
Plugin Name: Nooz
Plugin URI: http://mightydev.com/nooz/
Description: Simplified press release and media coverage management for corporate websites.
Author: Mighty Digital
Author URI: http://mightydigital.com
Version: 0.4.1
*/

require_once __DIR__ . '/inc/autoload.php';

$factory = new \WPAlchemy\Factory;

$nooz = new \MightyDev\WordPress\Nooz;

$nooz->init();

// setup cpt
$nooz->init_cpt();
$nooz->create_release_metabox( $factory );
$nooz->create_coverage_metabox( $factory );

$nooz->init_admin_menus();

$nooz->init_default_pages( $factory );

$nooz->init_content_filter();

$nooz->init_shortcodes();
