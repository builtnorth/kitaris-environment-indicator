<?php

declare(strict_types=1);

namespace KitarisEnvironmentIndicator\Tests;

use KitarisEnvironmentIndicator\Settings;
use WP_Mock;
use WP_Mock\Tools\TestCase;

/**
 * @covers \KitarisEnvironmentIndicator\Settings
 */
final class SettingsTest extends TestCase
{
	public function setUp(): void
	{
		parent::setUp();
		WP_Mock::userFunction('sanitize_text_field', ['return' => static fn ($v) => trim(strip_tags((string) $v))]);
		WP_Mock::userFunction('sanitize_hex_color', [
			'return' => static fn ($v) => preg_match('/^#([a-f0-9]{3}){1,2}$/i', (string) $v) ? $v : null,
		]);
	}

	public function test_keeps_valid_labels_and_colours_per_known_environment(): void
	{
		$clean = Settings::sanitize([
			'staging'    => ['text' => ' Stage <b>2</b> ', 'color' => '#ff0000'],
			'production' => ['text' => 'Live', 'color' => ''],
		]);

		$this->assertSame(
			[
				'staging'    => ['text' => 'Stage 2', 'color' => '#ff0000'],
				'production' => ['text' => 'Live'],
			],
			$clean
		);
	}

	public function test_drops_unknown_environments_invalid_colours_and_non_arrays(): void
	{
		$this->assertSame([], Settings::sanitize('nope'));
		$this->assertSame(
			[],
			Settings::sanitize([
				'qa'    => ['text' => 'QA', 'color' => '#00ff00'],
				'local' => ['text' => '', 'color' => 'red; background:url(x)'],
				'staging' => 'not-an-array',
			])
		);
	}

	public function test_keeps_the_custom_environment_row(): void
	{
		$this->assertSame(
			['custom' => ['text' => 'QA', 'color' => '#0891b2']],
			Settings::sanitize(['custom' => ['text' => 'QA', 'color' => '#0891b2']])
		);
	}

	public function test_config_hands_saved_overrides_to_the_indicator_shape(): void
	{
		WP_Mock::userFunction('get_option', [
			'args'   => [Settings::OPTION, []],
			'return' => ['local' => ['text' => 'Laptop', 'color' => '#123456']],
		]);

		$this->assertSame(['local' => ['text' => 'Laptop', 'color' => '#123456']], Settings::config());
	}
}
