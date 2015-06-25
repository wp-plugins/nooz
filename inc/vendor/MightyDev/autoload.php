<?php

if ( ! class_exists( '\MightyDev\Psr4ClassLoader' ) ) {
    require_once( __DIR__ . '/Psr4ClassLoader.php' );
}
$loader = new \MightyDev\Psr4ClassLoader();
$loader->addPrefix( 'MightyDev\\', __DIR__ );
$loader->addPrefix( 'MightyDev\\WordPress\\', __DIR__ );
$loader->addPrefix( 'MightyDev\\WordPress\\', __DIR__ . '/WordPress' );
$loader->addPrefix( 'MightyDev\\WordPress\\Plugin\\', __DIR__ . '/WordPress/Plugin' );
$loader->addPrefix( 'MightyDev\\WordPress\\Settings\\', __DIR__ . '/WordPress/Settings' );
$loader->addPrefix( 'MightyDev\\Templating\\', __DIR__ . '/Templating' );
$loader->register();
