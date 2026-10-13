<?php

declare(strict_types=1);

namespace KitarisEnvironmentIndicator;

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

	public static function register_setting(): void
	{
		register_setting(self::GROUP, self::OPTION, [
			'type'              => 'array',
			'sanitize_callback' => [self::class, 'sanitize'],
			'default'           => [],
		]);
	}

	public static function enqueue(string $hook): void
	{
		if ($hook !== 'settings_page_' . self::PAGE) {
			return;
		}

		wp_enqueue_style('wp-color-picker');
		wp_enqueue_script('wp-color-picker');
		wp_add_inline_script('wp-color-picker', 'jQuery(function($){$(".kitaris-ei-color").wpColorPicker();});');
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
		$current = wp_get_environment_type();
		?>
		<div class="wrap">
			<h1><?php esc_html_e('Environment Indicator', 'kitaris-environment-indicator'); ?></h1>
			<p>
				<?php
				printf(
					/* translators: %s: environment type, e.g. "staging". */
					esc_html__('This site is running as: %s. Set it with the WP_ENVIRONMENT_TYPE constant in wp-config.php.', 'kitaris-environment-indicator'),
					'<code>' . esc_html($current) . '</code>'
				);
				?>
			</p>
			<form action="options.php" method="post">
				<?php settings_fields(self::GROUP); ?>
				<table class="form-table" role="presentation">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e('Environment', 'kitaris-environment-indicator'); ?></th>
							<th scope="col"><?php esc_html_e('Label', 'kitaris-environment-indicator'); ?></th>
							<th scope="col"><?php esc_html_e('Dot colour', 'kitaris-environment-indicator'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach (self::DEFAULTS as $env => $default) : ?>
							<?php $name = self::OPTION . '[' . $env . ']'; ?>
							<tr>
								<th scope="row"><code><?php echo esc_html($env); ?></code></th>
								<td>
									<label class="screen-reader-text" for="kitaris-ei-<?php echo esc_attr($env); ?>-text">
										<?php
										/* translators: %s: environment type. */
										printf(esc_html__('Label for %s', 'kitaris-environment-indicator'), esc_html($env));
										?>
									</label>
									<input type="text" class="regular-text"
										id="kitaris-ei-<?php echo esc_attr($env); ?>-text"
										name="<?php echo esc_attr($name); ?>[text]"
										value="<?php echo esc_attr($saved[$env]['text'] ?? ''); ?>"
										placeholder="<?php echo esc_attr($default['text']); ?>">
								</td>
								<td>
									<label class="screen-reader-text" for="kitaris-ei-<?php echo esc_attr($env); ?>-color">
										<?php
										/* translators: %s: environment type. */
										printf(esc_html__('Dot colour for %s', 'kitaris-environment-indicator'), esc_html($env));
										?>
									</label>
									<input type="text" class="kitaris-ei-color"
										id="kitaris-ei-<?php echo esc_attr($env); ?>-color"
										name="<?php echo esc_attr($name); ?>[color]"
										value="<?php echo esc_attr($saved[$env]['color'] ?? ''); ?>"
										data-default-color="<?php echo esc_attr($default['color']); ?>">
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
