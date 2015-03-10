<?php

if ( ! class_exists( '\Symfony\Component\ClassLoader\Psr4ClassLoader' ) ) {
	require_once( __DIR__ . '/Symfony/Component/ClassLoader/Psr4ClassLoader.php' );
}
$loader = new \Symfony\Component\ClassLoader\Psr4ClassLoader();
$loader->addPrefix( 'WPAlchemy\\', __DIR__ . '/WPAlchemy' );
$loader->addPrefix( 'WPAlchemy\\Settings', __DIR__ . '/WPAlchemy' );
$loader->addPrefix( 'MightyDev\\WordPress\\', __DIR__ . '/MightyDev' );
$loader->register();
