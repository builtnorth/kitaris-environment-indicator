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

	public function test_reset_clears_every_override(): void
	{
		$this->assertSame(
			[],
			Settings::sanitize([
				'reset'   => 'Reset to defaults',
				'staging' => ['text' => 'Stage', 'color' => '#ff0000'],
			])
		);
	}

	public function test_badge_config_lets_code_win_over_saved_settings(): void
	{
		$saved = ['local' => ['text' => 'Laptop']];
		WP_Mock::userFunction('get_option', [
			'args'   => [Settings::OPTION, []],
			'return' => $saved,
		]);
		WP_Mock::onFilter('kitaris_environment_indicator_config')
			->with($saved)
			->reply([
				'local'   => ['text' => 'Laptop'],
				'staging' => ['text' => 'QA', 'color' => '#0891b2'],
				'bogus'   => ['text' => 'dropped'],
			]);

		$this->assertSame(
			[
				'local'   => ['text' => 'Laptop'],
				'staging' => ['text' => 'QA', 'color' => '#0891b2'],
			],
			Settings::badge_config()
		);
	}

	public function test_settings_page_filter_removes_the_page(): void
	{
		WP_Mock::onFilter('kitaris_environment_indicator_settings_page')
			->with(true)
			->reply(false);
		WP_Mock::userFunction('add_options_page', ['times' => 0]);

		Settings::add_page();

		$this->assertConditionsMet();
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
