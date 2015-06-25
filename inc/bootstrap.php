<?php

if ( ! defined( 'WPINC' ) ) {
    die;
}

require_once( __DIR__ . '/vendor/MightyDev/autoload.php' );

require_once( __DIR__ . '/autoload.php' );

if ( ! class_exists( '\Twig_Autoloader' ) ) {
    require_once( __DIR__ . '/vendor/Twig/Autoloader.php' );
    Twig_Autoloader::register();
}
