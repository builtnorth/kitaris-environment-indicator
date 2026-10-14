<?php

declare(strict_types=1);

namespace KitarisEnvironmentIndicator;

/**
 * Kitaris names for the bundled package's constant and filters.
 *
 * The package (siratik/environment-indicator) owns the behavior; this plugin
 * only forwards its own names into it, so the readme can document one prefix.
 * The siratik_* names keep working alongside these.
 *
 * - KITARIS_ENVIRONMENT_INDICATOR_CUSTOM   → the default custom name
 * - kitaris_environment_indicator_custom  → runs inside siratik_environment_indicator_custom
 * - kitaris_environment_indicator_show    → runs inside siratik_environment_indicator_show
 */
final class Aliases
{
	public static function register(): void
	{
		// Early, so the constant is a default that later filters can still change.
		add_filter('siratik_environment_indicator_custom', [self::class, 'custom_constant'], 5);
		// Late, so Kitaris filters see what other code returned first.
		add_filter('siratik_environment_indicator_custom', [self::class, 'custom_filter'], 20, 2);
		add_filter('siratik_environment_indicator_show', [self::class, 'show_filter'], 20, 2);
	}

	/**
	 * KITARIS_ENVIRONMENT_INDICATOR_CUSTOM, when defined, replaces the default name.
	 *
	 * @param mixed $custom Custom name so far.
	 * @return mixed
	 */
	public static function custom_constant($custom)
	{
		return \defined('KITARIS_ENVIRONMENT_INDICATOR_CUSTOM') ? (string) \KITARIS_ENVIRONMENT_INDICATOR_CUSTOM : $custom;
	}

	/**
	 * @param mixed $custom Custom name so far.
	 * @param mixed $type   Environment type from WordPress core.
	 * @return mixed
	 */
	public static function custom_filter($custom, $type = '')
	{
		/**
		 * Filters the custom environment name shown instead of WordPress's type.
		 *
		 * Return '' to show the type. Defaults to the
		 * KITARIS_ENVIRONMENT_INDICATOR_CUSTOM constant.
		 *
		 * @param string $custom Custom name, or '' for none.
		 * @param string $type   Environment type from WordPress core.
		 */
		return apply_filters('kitaris_environment_indicator_custom', $custom, (string) $type);
	}

	/**
	 * @param mixed $show        Whether to show the badge so far.
	 * @param mixed $environment Environment the badge shows.
	 * @return mixed
	 */
	public static function show_filter($show, $environment = '')
	{
		/**
		 * Filters whether the current user sees the badge.
		 *
		 * @param bool   $show        Default: the user can edit posts.
		 * @param string $environment Environment the badge shows.
		 */
		return apply_filters('kitaris_environment_indicator_show', (bool) $show, (string) $environment);
	}
}
