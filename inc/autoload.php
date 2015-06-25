<?php

if ( ! class_exists( '\Symfony\Component\ClassLoader\Psr4ClassLoader' ) ) {
	require_once( __DIR__ . '/vendor/Symfony/Component/ClassLoader/Psr4ClassLoader.php' );
}
$loader = new \Symfony\Component\ClassLoader\Psr4ClassLoader();
$loader->addPrefix( 'WPAlchemy\\', __DIR__ . '/vendor/WPAlchemy' );
$loader->addPrefix( 'WPAlchemy\\Settings', __DIR__ . '/vendor/WPAlchemy' );
$loader->addPrefix( 'MightyDev\\WordPress\\Plugin', __DIR__ );
$loader->register();
require_once(__DIR__ . '/vendor/EDD/EDD_SL_Plugin_Updater.php');
require_once(__DIR__ . '/vendor/WPAlchemy/helpers.php');
