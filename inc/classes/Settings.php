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
										placeholder="<?php echo esc_attr($default['text']); ?>"
										aria-describedby="kitaris-ei-<?php echo esc_attr($env); ?>-text-description">
									<p class="description" id="kitaris-ei-<?php echo esc_attr($env); ?>-text-description">
										<?php
										printf(
											/* translators: %s: default label, e.g. "Staging". */
											esc_html__('Text shown in the admin bar. Leave empty to use "%s".', 'kitaris-environment-indicator'),
											esc_html($default['text'])
										);
										?>
									</p>
								</td>
								<td>
									<label class="screen-reader-text" for="kitaris-ei-<?php echo esc_attr($env); ?>-color">
										<?php
										/* translators: %s: environment type. */
										printf(esc_html__('Dot colour for %s', 'kitaris-environment-indicator'), esc_html($env));
										?>
									</label>
									<input type="color"
										id="kitaris-ei-<?php echo esc_attr($env); ?>-color"
										name="<?php echo esc_attr($name); ?>[color]"
										value="<?php echo esc_attr($saved[$env]['color'] ?? $default['color']); ?>"
										aria-describedby="kitaris-ei-<?php echo esc_attr($env); ?>-color-description">
									<p class="description" id="kitaris-ei-<?php echo esc_attr($env); ?>-color-description">
										<?php
										printf(
											/* translators: %s: default hex colour, e.g. "#db7800". */
											esc_html__('Colour of the dot next to the label. Default: %s.', 'kitaris-environment-indicator'),
											'<code>' . esc_html($default['color']) . '</code>'
										);
										?>
									</p>
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
