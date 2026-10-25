<?php

declare(strict_types=1);

/**
 * Plugin Name:       Kitaris Environment Indicator
 * Plugin URI:
 * Description:       Minimal indicator that shows which environment you are on (local, development, staging, production, or custom) in the admin bar.
 * Version:           1.0.3
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            Kitaris
 * Author URI:        https://kitaris.com
 * License:           GPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       kitaris-environment-indicator
 *
 * @package KitarisEnvironmentIndicator
 */

use KitarisEnvironmentIndicator\Aliases;
use KitarisEnvironmentIndicator\Plugin;
use KitarisEnvironmentIndicator\Settings;

defined('ABSPATH') || exit;

// Load the bundled dependencies. A site that installs the plugin with Composer
// loads them through its own autoloader, so vendor/ may not exist.
$kitaris_environment_indicator_autoload = __DIR__ . '/vendor/autoload.php';
if (is_readable($kitaris_environment_indicator_autoload)) {
	require_once $kitaris_environment_indicator_autoload;
}

/**
 * Missing or partial vendor/ (a deploy without dependencies, an unfinished
 * upload): stay inert and tell admins, rather than fatal on every request.
 */
if (! class_exists(Settings::class)) {
	add_action(
		'admin_notices',
		static function (): void {
			if (current_user_can('activate_plugins')) {
				printf(
					'<div class="notice notice-error"><p>%s</p></div>',
					esc_html__('Kitaris Environment Indicator could not load its bundled files (vendor/ is missing or incomplete). Reinstall the plugin.', 'kitaris-environment-indicator')
				);
			}
		}
	);

	return;
}

Settings::register();
Aliases::register();

// siratik/package-loader settles which bundled copy of each package wins once
// plugins_loaded has run, so the indicator is only touched after that.
add_action('init', [Plugin::class, 'boot']);
