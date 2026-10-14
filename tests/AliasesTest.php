<?php

declare(strict_types=1);

namespace KitarisEnvironmentIndicator\Tests;

use KitarisEnvironmentIndicator\Aliases;
use WP_Mock;
use WP_Mock\Tools\TestCase;

/**
 * @covers \KitarisEnvironmentIndicator\Aliases
 */
final class AliasesTest extends TestCase
{
	public function test_registers_around_the_package_filters(): void
	{
		WP_Mock::expectFilterAdded('siratik_environment_indicator_custom', [Aliases::class, 'custom_constant'], 5);
		WP_Mock::expectFilterAdded('siratik_environment_indicator_custom', [Aliases::class, 'custom_filter'], 20, 2);
		WP_Mock::expectFilterAdded('siratik_environment_indicator_show', [Aliases::class, 'show_filter'], 20, 2);

		Aliases::register();

		$this->assertConditionsMet();
	}

	public function test_without_the_constant_the_name_passes_through(): void
	{
		$this->assertSame('from-siratik', Aliases::custom_constant('from-siratik'));
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_the_constant_replaces_the_default_name(): void
	{
		define('KITARIS_ENVIRONMENT_INDICATOR_CUSTOM', 'qa');

		$this->assertSame('qa', Aliases::custom_constant('from-siratik'));
	}

	public function test_the_custom_filter_runs_inside_the_package_filter(): void
	{
		WP_Mock::onFilter('kitaris_environment_indicator_custom')
			->with('qa', 'staging')
			->reply('uat');

		$this->assertSame('uat', Aliases::custom_filter('qa', 'staging'));
	}

	public function test_the_show_filter_runs_inside_the_package_filter(): void
	{
		WP_Mock::onFilter('kitaris_environment_indicator_show')
			->with(false, 'staging')
			->reply(true);

		$this->assertTrue(Aliases::show_filter(false, 'staging'));
	}
}
