<?php

declare(strict_types=1);

namespace KitarisEnvironmentIndicator;

use Siratik\EnvironmentIndicator\Indicator;

/**
 * Settings → Environment Indicator: a label and a dot colour per environment.
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
		'local'       => ['text' => 'Local', 'color' => '#7c3aed'],
		'development' => ['text' => 'Development', 'color' => '#3858e9'],
		'staging'     => ['text' => 'Staging', 'color' => '#db7800'],
		'production'  => ['text' => 'Production', 'color' => '#0cb034'],
		// A custom name from the SIRATIK_ENVIRONMENT_INDICATOR_CUSTOM constant or
		// the siratik_environment_indicator_custom filter; an empty label shows
		// the name itself.
		'custom'      => ['text' => '', 'color' => '#64748b'],
	];

	public static function register(): void
	{
		add_action('admin_menu', [self::class, 'add_page']);
		add_action('admin_init', [self::class, 'register_setting']);
		add_action('admin_enqueue_scripts', [self::class, 'enqueue']);
	}

	public static function add_page(): void
	{
		add_options_page(
			__('Environment Indicator', 'kitaris-environment-indicator'),
			__('Environment Indicator', 'kitaris-environment-indicator'),
			'manage_options',
			self::PAGE,
			[self::class, 'render']
		);
	}

	/**
	 * Core colour picker plus the page's own styles and live preview.
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
	 * Keep only known environments, a plain-text label and a valid hex colour.
	 *
	 * @param mixed $input Raw form value.
	 * @return array<string, array{text?: string, color?: string}>
	 */
	public static function sanitize($input): array
	{
		$clean = [];
		if (! is_array($input)) {
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

	public static function render(): void
	{
		if (! current_user_can('manage_options')) {
			return;
		}

		$saved   = self::config();
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
								<span><?php esc_html_e('Dot colour', 'kitaris-environment-indicator'); ?></span>
								<span><?php esc_html_e('Preview', 'kitaris-environment-indicator'); ?></span>
							</div>
							<?php
							foreach (self::DEFAULTS as $env => $default) {
								self::render_row($env, $default, $saved[$env] ?? [], $env === $current_row, $current);
							}
							?>
						</div>
					</div>
				</div>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * One environment: slug, label, dot colour and badge preview.
	 *
	 * @param array{text: string, color: string}   $default Built-in label and colour.
	 * @param array{text?: string, color?: string} $saved   Saved overrides.
	 */
	private static function render_row(string $env, array $default, array $saved, bool $is_current, string $current): void
	{
		$name  = self::OPTION . '[' . $env . ']';
		$id    = 'kitaris-ei-' . $env;
		$color = $saved['color'] ?? $default['color'];

		// What the badge shows when the label is empty.
		$fallback = $default['text'];
		if ($env === 'custom') {
			$fallback = $is_current
				? ucwords(str_replace(['-', '_'], ' ', $current))
				: __('Custom', 'kitaris-environment-indicator');
		}
		$text = ($saved['text'] ?? '') !== '' ? $saved['text'] : $fallback;
		?>
		<div class="kitaris-ei-row">
			<div class="kitaris-ei-row__env">
				<code><?php echo esc_html($env); ?></code>
				<?php if ($is_current) : ?>
					<span class="kitaris-ei-current"><?php esc_html_e('This site', 'kitaris-environment-indicator'); ?></span>
				<?php endif; ?>
			</div>
			<div class="kitaris-ei-field">
				<label class="kitaris-ei-field__label" for="<?php echo esc_attr($id); ?>-text">
					<?php
					/* translators: %s: environment type. */
					printf(esc_html__('Label for %s', 'kitaris-environment-indicator'), esc_html($env));
					?>
				</label>
				<input type="text" class="kitaris-ei-label"
					id="<?php echo esc_attr($id); ?>-text"
					name="<?php echo esc_attr($name); ?>[text]"
					value="<?php echo esc_attr($saved['text'] ?? ''); ?>"
					placeholder="<?php echo esc_attr($env === 'custom' ? __('Environment name', 'kitaris-environment-indicator') : $default['text']); ?>">
			</div>
			<div class="kitaris-ei-field">
				<label class="kitaris-ei-field__label" for="<?php echo esc_attr($id); ?>-color">
					<?php
					/* translators: %s: environment type. */
					printf(esc_html__('Dot colour for %s', 'kitaris-environment-indicator'), esc_html($env));
					?>
				</label>
				<input type="text" class="kitaris-ei-color"
					id="<?php echo esc_attr($id); ?>-color"
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
		return ! class_exists(Indicator::class) || Indicator::instance()->is_environment_type_set();
	}
}
