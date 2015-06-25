<?php

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

require_once(__DIR__ . '/inc/bootstrap.php');

use MightyDev\WordPress\Plugin\NoozCore;

NoozCore::get_instance()->uninstall();
