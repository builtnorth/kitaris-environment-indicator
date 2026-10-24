<?php

declare(strict_types=1);

namespace KitarisEnvironmentIndicator;

defined('ABSPATH') || exit;

use Siratik\EnvironmentIndicator\Indicator;

/**
 * Settings → Environment Indicator: a label and a dot color per environment.
 *
 * Empty fields keep the indicator's built-in defaults.
 */
final class Settings
{
	public const OPTION = 'kitaris_environment_indicator_settings';

	private const PAGE  = 'kitaris-environment-indicator';
	private const GROUP = 'kitaris_environment_indicator';

	/** The indicator's own defaults, shown as placeholders. */
	private const DEFAULTS = [
		'local'       => ['text' => 'Local', 'color' => '#4ab866'],
		'development' => ['text' => 'Development', 'color' => '#4ab866'],
		'staging'     => ['text' => 'Staging', 'color' => '#f0b849'],
		'production'  => ['text' => 'Production', 'color' => '#cc1818'],
		// A custom name from the KITARIS_ENVIRONMENT_INDICATOR_CUSTOM constant or
		// the kitaris_environment_indicator_custom filter; an empty label shows
		// the name itself.
		'custom'      => ['text' => '', 'color' => '#949494'],
	];

	public static function register(): void
	{
		add_action('admin_menu', [self::class, 'add_page']);
		add_action('admin_init', [self::class, 'register_setting']);
		add_action('admin_enqueue_scripts', [self::class, 'enqueue']);
	}

	public static function add_page(): void
	{
		/**
		 * Filters whether Settings → Environment Indicator is shown.
		 *
		 * Return false to remove the page, e.g. when labels and colors are set
		 * in code. Saved settings still apply.
		 *
		 * @param bool $show Whether to add the settings page. Default true.
		 */
		if (! apply_filters('kitaris_environment_indicator_settings_page', true)) {
			return;
		}

		add_options_page(
			__('Environment Indicator', 'kitaris-environment-indicator'),
			__('Environment Indicator', 'kitaris-environment-indicator'),
			'manage_options',
			self::PAGE,
			[self::class, 'render']
		);
	}

	/**
	 * Core color picker plus the page's own styles and live preview.
	 */
	public static function enqueue(string $hook_suffix): void
	{
		// Match the page slug, not the full suffix: admin menu tools can move the
		// page to another parent (settings_page_… becomes admin_page_… etc.).
		if (! str_ends_with($hook_suffix, '_page_' . self::PAGE)) {
			return;
		}

		$plugin_file = dirname(__DIR__, 2) . '/kitaris-environment-indicator.php';
		$assets      = dirname(__DIR__, 2) . '/assets/';

		wp_enqueue_style(
			'kitaris-environment-indicator-settings',
			plugins_url('assets/settings.css', $plugin_file),
			['wp-color-picker'],
			(string) filemtime($assets . 'settings.css')
		);
		wp_enqueue_script(
			'kitaris-environment-indicator-settings',
			plugins_url('assets/settings.js', $plugin_file),
			['jquery', 'wp-color-picker'],
			(string) filemtime($assets . 'settings.js'),
			true
		);
	}

	public static function register_setting(): void
	{
		register_setting(self::GROUP, self::OPTION, [
			'type'              => 'array',
			'sanitize_callback' => [self::class, 'sanitize'],
			'default'           => [],
		]);
	}

	/**
	 * Keep only known environments, a plain-text label and a valid hex color.
	 *
	 * @param mixed $input Raw form value.
	 * @return array<string, array{text?: string, color?: string}>
	 */
	public static function sanitize($input): array
	{
		$clean = [];
		// "Reset to defaults" posts reset=1 alongside the fields.
		if (! is_array($input) || ! empty($input['reset'])) {
			return $clean;
		}

		foreach (array_keys(self::DEFAULTS) as $env) {
			$row   = is_array($input[$env] ?? null) ? $input[$env] : [];
			$text  = sanitize_text_field((string) ($row['text'] ?? ''));
			$color = sanitize_hex_color((string) ($row['color'] ?? '')) ?? '';

			if ($text !== '') {
				$clean[$env]['text'] = $text;
			}
			if ($color !== '') {
				$clean[$env]['color'] = $color;
			}
		}

		return $clean;
	}

	/**
	 * Saved overrides in the shape Indicator::set_config() expects.
	 *
	 * @return array<string, array{text?: string, color?: string}>
	 */
	public static function config(): array
	{
		return self::sanitize(get_option(self::OPTION, []));
	}

	/**
	 * Saved overrides with any set in code on top: what the badge uses.
	 *
	 * @return array<string, array{text?: string, color?: string}>
	 */
	public static function badge_config(): array
	{
		$saved = self::config();

		/**
		 * Filters the labels and colors the badge uses.
		 *
		 * Receives the saved settings, keyed by environment (local, development,
		 * staging, production, custom), each with an optional `text` and `color`.
		 * Values set here win over the settings page.
		 *
		 * @param array<string, array{text?: string, color?: string}> $config Saved settings.
		 */
		$config = apply_filters('kitaris_environment_indicator_config', $saved);

		return self::sanitize($config);
	}

	public static function render(): void
	{
		if (! current_user_can('manage_options')) {
			return;
		}

		$saved   = self::config();
		// The current row previews what the badge shows, code overrides included.
		$badge   = self::badge_config();
		$current = self::current_environment();
		// A name outside the four core types is shown by the Custom row.
		$current_row = isset(self::DEFAULTS[$current]) && $current !== 'custom' ? $current : ($current !== '' ? 'custom' : '');
		?>
		<div class="wrap kitaris-ei">
			<h1><?php esc_html_e('Environment Indicator', 'kitaris-environment-indicator'); ?></h1>
			<?php if (! self::is_environment_type_set()) : ?>
				<div class="notice notice-warning inline">
					<p>
						<?php
						printf(
							/* translators: %s: the WP_ENVIRONMENT_TYPE constant. */
							esc_html__('%s is not set to local, development, staging or production, so WordPress assumes production. Set it in wp-config.php, for example:', 'kitaris-environment-indicator'),
							'<code>WP_ENVIRONMENT_TYPE</code>'
						);
						?>
						<code>define( 'WP_ENVIRONMENT_TYPE', 'staging' );</code>
					</p>
				</div>
			<?php endif; ?>
			<form action="options.php" method="post">
				<?php settings_fields(self::GROUP); ?>
				<div class="postbox">
					<div class="inside">
						<div class="kitaris-ei-list">
							<div class="kitaris-ei-head" aria-hidden="true">
								<span><?php esc_html_e('Environment', 'kitaris-environment-indicator'); ?></span>
								<span><?php esc_html_e('Label', 'kitaris-environment-indicator'); ?></span>
								<span><?php esc_html_e('Dot color', 'kitaris-environment-indicator'); ?></span>
								<span><?php esc_html_e('Preview', 'kitaris-environment-indicator'); ?></span>
							</div>
							<?php
							foreach (self::DEFAULTS as $env => $default) {
								$is_current = $env === $current_row;
								self::render_row($env, $default, $saved[$env] ?? [], $is_current, $current, $is_current ? ($badge[$env] ?? []) : null);
							}
							?>
						</div>
					</div>
				</div>
				<?php // Save comes first so Enter in a field saves; CSS puts Reset on its left. ?>
				<p class="submit kitaris-ei-actions">
					<?php
					submit_button(null, 'primary', 'submit', false);
					submit_button(
						__('Reset to defaults', 'kitaris-environment-indicator'),
						'secondary kitaris-ei-reset',
						self::OPTION . '[reset]',
						false,
						[
							'id'           => 'kitaris-ei-reset',
							'data-confirm' => __('Reset every label and color to its default?', 'kitaris-environment-indicator'),
						]
					);
					?>
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * One environment: slug, label, dot color and badge preview.
	 *
	 * @param array{text: string, color: string}   $default Built-in label and color.
	 * @param array{text?: string, color?: string} $saved   Saved overrides.
	 */
	/**
	 * @param array<string, string>      $saved   Saved values, shown in the fields.
	 * @param array<string, string>|null $preview What the badge uses for this row (null: the saved values).
	 */
	private static function render_row(string $env, array $default, array $saved, bool $is_current, string $current, ?array $preview = null): void
	{
		$name  = self::OPTION . '[' . $env . ']';
		$id    = 'kitaris-ei-' . $env;
		$shown = $preview ?? $saved;
		$color = $shown['color'] ?? $default['color'];

		// What the badge shows when the label is empty.
		$fallback = $default['text'];
		if ($env === 'custom') {
			$fallback = $is_current
				? ucwords(str_replace(['-', '_'], ' ', $current))
				: __('Custom', 'kitaris-environment-indicator');
		}
		$text = ($shown['text'] ?? '') !== '' ? $shown['text'] : $fallback;
		?>
		<div class="kitaris-ei-row">
			<div class="kitaris-ei-row__env">
				<code><?php echo esc_html($env); ?></code>
				<?php if ($is_current) : ?>
					<span class="kitaris-ei-current"><?php esc_html_e('This site', 'kitaris-environment-indicator'); ?></span>
				<?php endif; ?>
			</div>
			<div class="kitaris-ei-field kitaris-ei-field--text">
				<label class="kitaris-ei-field__label" for="<?php echo esc_attr($id); ?>-text"><?php esc_html_e('Label', 'kitaris-environment-indicator'); ?></label>
				<input type="text" class="kitaris-ei-label"
					id="<?php echo esc_attr($id); ?>-text"
					aria-label="<?php
						/* translators: %s: environment type. */
						echo esc_attr(sprintf(__('Label for %s', 'kitaris-environment-indicator'), $env));
					?>"
					name="<?php echo esc_attr($name); ?>[text]"
					value="<?php echo esc_attr($saved['text'] ?? ''); ?>"
					placeholder="<?php echo esc_attr($env === 'custom' ? __('Environment name', 'kitaris-environment-indicator') : $default['text']); ?>">
			</div>
			<div class="kitaris-ei-field kitaris-ei-field--color">
				<label class="kitaris-ei-field__label" for="<?php echo esc_attr($id); ?>-color"><?php esc_html_e('Dot color', 'kitaris-environment-indicator'); ?></label>
				<input type="text" class="kitaris-ei-color"
					id="<?php echo esc_attr($id); ?>-color"
					aria-label="<?php
						/* translators: %s: environment type. */
						echo esc_attr(sprintf(__('Dot color for %s', 'kitaris-environment-indicator'), $env));
					?>"
					name="<?php echo esc_attr($name); ?>[color]"
					value="<?php echo esc_attr($color); ?>"
					data-default-color="<?php echo esc_attr($default['color']); ?>">
			</div>
			<div class="kitaris-ei-row__preview">
				<span class="kitaris-ei-preview" style="<?php echo esc_attr('--kitaris-ei-dot: ' . $color); ?>" aria-hidden="true">
					<span class="kitaris-ei-preview__dot"></span>
					<span class="kitaris-ei-preview__text" data-fallback="<?php echo esc_attr($fallback); ?>"><?php echo esc_html($text); ?></span>
				</span>
			</div>
		</div>
		<?php
	}

	/**
	 * The environment the badge is showing, including custom names.
	 */
	private static function current_environment(): string
	{
		if (class_exists(Indicator::class)) {
			return Indicator::instance()->get_environment();
		}

		return wp_get_environment_type();
	}

	/**
	 * Whether WP_ENVIRONMENT_TYPE is set to one of WordPress's four types.
	 */
	private static function is_environment_type_set(): bool
	{
		// An older copy of the package (bundled by another plugin) may have won
		// the version check and predate this method; then skip the warning.
		if (! class_exists(Indicator::class) || ! method_exists(Indicator::class, 'is_environment_type_set')) {
			return true;
		}

		return Indicator::instance()->is_environment_type_set();
	}
}
