<?php

use \Symfony\Component\ClassLoader\Psr4ClassLoader;

if ( ! class_exists( '\Symfony\Component\ClassLoader\Psr4ClassLoader' ) ) {
    require_once( dirname( __FILE__ ) . '/vendor/Symfony/Component/ClassLoader/Psr4ClassLoader.php' );
}
$loader = new Psr4ClassLoader();
$loader->addPrefix( 'MightyDev', dirname( __FILE__ ) . '/vendor/MightyDev' );
$loader->addPrefix( 'MightyDev\\Templating', dirname( __FILE__ ) . '/vendor/MightyDev/Templating' );
$loader->addPrefix( 'MightyDev\\WordPress', dirname( __FILE__ ) . '/vendor/MightyDev/WordPress' );
$loader->addPrefix( 'MightyDev\\WordPress\\Plugin', dirname( __FILE__ ) . '/vendor/MightyDev/WordPress/Plugin' );
$loader->addPrefix( 'MightyDev\\WordPress\\Plugin', dirname( __FILE__ ) );
$loader->addPrefix( 'WPAlchemy', dirname( __FILE__ ) . '/vendor/WPAlchemy' );
$loader->register();
if ( ! class_exists( '\Twig_Autoloader' ) ) {
    require_once( dirname( __FILE__ ) . '/vendor/Twig/Autoloader.php' );
    Twig_Autoloader::register();
}
if ( ! class_exists( '\WPAlchemy_MetaBox' ) ) {
    require_once dirname( __FILE__ ) . '/vendor/WPAlchemy/MetaBox.php';
}
