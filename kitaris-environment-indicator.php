<?php

declare(strict_types=1);

/**
 * Plugin Name:       Kitaris Environment Indicator
 * Plugin URI:        https://kitaris.com
 * Description:       Shows which environment you are on (local, development, staging or production) in the admin bar, so production is never mistaken for staging.
 * Version:           1.0.0
 * Requires at least: 6.7
 * Requires PHP:      8.1
 * Author:            Kitaris
 * Author URI:        https://kitaris.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       kitaris-environment-indicator
 *
 * @package KitarisEnvironmentIndicator
 */

use KitarisEnvironmentIndicator\Settings;
use Siratik\EnvironmentIndicator\Indicator;

defined('ABSPATH') || exit;

// Release zip: load the bundled vendor/. In the monorepo the root autoloader
// already covers this plugin and the siratik packages.
$kitaris_environment_indicator_autoload = __DIR__ . '/vendor/autoload.php';
if (! class_exists(Settings::class) && is_readable($kitaris_environment_indicator_autoload)) {
	require_once $kitaris_environment_indicator_autoload;
}

Settings::register();

// siratik/package-loader settles which bundled copy of each package wins once
// plugins_loaded has run, so the indicator is only touched after that.
add_action('init', 'kitaris_environment_indicator_boot');

/**
 * Apply the saved labels and colours, then boot the indicator unless another
 * plugin bundling the same package already has.
 */
function kitaris_environment_indicator_boot(): void
{
	if (! class_exists(Indicator::class)) {
		return;
	}

	$indicator = Indicator::instance();
	$indicator->set_config(Settings::badge_config());

	if (! has_action('admin_bar_menu', [$indicator, 'add_environment_menu'])) {
		$indicator->boot();
	}
}
