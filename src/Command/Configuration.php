<?php
declare(strict_types=1);
/**
 * @package    zesk
 * @subpackage core
 * @author     kent
 * @copyright  Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace zesk\Command;

use Throwable;
use zesk\Configuration\Loader;

/**
 * List configuration files which are examined and loaded for the application.
 *
 * Practical uses for this tool:
 *
 * - "loaded" - What's affecting the current configuration? Configure or change existing configuration files.
 * - "not-loaded" - Add a local development or determine why files are not loading.
 * - "skipped" - Determine files which have errors
 * - "externals" - Determine required environment for application (these are variables in the configuration files
 * which are not defined within the files themselves)
 * - "missing-classes" - May show configuration classes which are deprecated.
 * - "top-level-scalar" - Most configuration is 2-levels deep (["className", "option"]) - top-level scalar values
 * typically are environment values.
 *
 * @category Debugging
 * @author   kent
 *
 */
class Configuration extends SimpleCommand
{
	protected array $shortcuts = [
		'conf',
		'config',
		'configuration',
	];

	protected array $argumentTypes = [
		'loaded'           => 'boolean',
		'not-loaded'       => 'boolean',
		'skipped'          => 'boolean',
		'externals'        => 'boolean',
		'missing-classes'  => 'boolean',
		'top-level-scalar' => 'boolean',
	];

	protected array $option_help = [
		'loaded'           => 'Show loaded configuration files',
		'not-loaded'       => 'Show configuration files which would be loaded but were not because they did not exist',
		'skipped'          => 'Show configuration files which are skipped for any reason',
		'externals'        => 'Show external variables which affect the configuration',
		'missing-classes'  => 'Show missing classes in the configuration root',
		'top-level-scalar' => 'Show top-level configuration settings which are scalars (globals or environment variables)',
	];

	/**
	 *
	 * @return int
	 */
	public function run(): int
	{
		$app = $this->application;
		$variables = $app->loaderVariables();

		[
			$missingClasses,
			$warning_top_levels,
		] = $this->collectMisnamedClassConfigurations();
		$variables['-missing-classes-'] = $missingClasses;
		$variables['-warning-top-'] = $warning_top_levels;

		$flags = $this->showFlags();

		$result = [];
		foreach ([
			'loaded'           => [
				'Loaded',
				Loader::PROCESSED,
			],
			'not-loaded'       => [
				'Missing',
				Loader::MISSING,
			],
			'skipped'          => [
				'Skipped',
				Loader::SKIPPED,
			],
			'externals'        => [
				'External variables',
				Loader::EXTERNALS,
			],
			'missing-classes'  => [
				'Missing classes',
				'-missing-classes-',
			],
			'top-level-scalar' => [
				'Top-level variables which are scalar',
				'-warning-top-',
			],
		] as $flag => [$key, $variablesKey]) {
			$list = $variables[$variablesKey];
			if ($flags[$flag] && count($list)) {
				$result[$key] = $this->outputList($list);
			}
		}
		$this->renderFormat($result);
		return 0;
	}

	/**
	 * @return array
	 */
	private function showFlags(): array
	{
		return $this->options([
			'loaded'           => true,
			'not-loaded'       => true,
			'skipped'          => true,
			'externals'        => true,
			'missing-classes'  => true,
			'top-level-scalar' => true,
		]);
	}

	/**
	 *
	 * @return
	 */
	public function collectMisnamedClassConfigurations(): array
	{
		$config = $this->application->configuration;
		$missing = $warning = [];
		foreach ($config as $key => $next) {
			if ($next instanceof \zesk\Configuration) {
				try {
					if (!class_exists($key)) {
						$missing[] = $key;
					}
				} catch (Throwable $e) {
					$missing[] = $key;
				}
			} else {
				$warning[] = $key;
			}
		}
		return [
			$missing,
			$warning,
		];
	}

	/**
	 * @param array $list
	 * @return array
	 */
	private function outputList(array $list): array
	{
		return $list;
	}
}
