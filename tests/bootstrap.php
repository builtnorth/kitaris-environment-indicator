<?php
/**
 * PHPUnit bootstrap.
 *
 * @package KitarisEnvironmentIndicator
 */

// Composer autoloader, falling back to the monorepo root's when this plugin
// has no standalone vendor/ install (the normal dev setup).
$autoloader = dirname(__DIR__) . '/vendor/autoload.php';
if (! file_exists($autoloader)) {
	$autoloader = dirname(__DIR__, 4) . '/vendor/autoload.php';
}
require_once $autoloader;

WP_Mock::bootstrap();

if (! defined('ABSPATH')) {
	define('ABSPATH', '/tmp/wordpress/');
}
