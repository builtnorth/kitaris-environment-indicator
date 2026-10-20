<?php

declare(strict_types=1);

namespace KitarisEnvironmentIndicator;

defined('ABSPATH') || exit;

use Siratik\EnvironmentIndicator\Indicator;

/**
 * Boots the indicator.
 *
 * A class method rather than a global function: if two copies of the plugin
 * are active (say, one from Composer and one from WordPress.org), the second
 * copy's hooks point at the same callbacks and WordPress keeps one of each,
 * where a second global function would be a fatal redeclaration.
 */
final class Plugin
{
	/**
	 * Apply the saved labels and colors, then boot the indicator unless another
	 * plugin bundling the same package already has.
	 */
	public static function boot(): void
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
}
