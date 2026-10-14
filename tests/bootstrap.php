<?php
/**
 * PHPUnit bootstrap.
 *
 * @package KitarisEnvironmentIndicator
 */

// The plugin's own Composer autoloader, falling back to the project's when the
// plugin was installed by Composer into wp-content/plugins and has no vendor/.
$autoloader = dirname(__DIR__) . '/vendor/autoload.php';
if (! file_exists($autoloader)) {
	$autoloader = dirname(__DIR__, 4) . '/vendor/autoload.php';
}
require_once $autoloader;

WP_Mock::bootstrap();

if (! defined('ABSPATH')) {
	define('ABSPATH', '/tmp/wordpress/');
}
