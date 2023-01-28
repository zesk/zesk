<?php declare(strict_types=1);
namespace zesk;

use Throwable;

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
 * @author kent
 *
 */
class Command_Configuration extends Command_Base {
	protected array $shortcuts = ['conf', 'config', 'configuration'];

	protected array $option_types = [
		'loaded' => 'boolean',
		'not-loaded' => 'boolean',
		'skipped' => 'boolean',
		'externals' => 'boolean',
		'missing-classes' => 'boolean',
		'top-level-scalar' => 'boolean',
	];

	protected array $option_help = [
		'loaded' => 'Show loaded configuration files',
		'not-loaded' => 'Show configuration files which would be loaded but were not because they did not exist',
		'skipped' => 'Show configuration files which are skipped for any reason',
		'externals' => 'Show external variables which affect the configuration',
		'missing-classes' => 'Show missing classes in the configuration root',
		'top-level-scalar' => 'Show top-level configuration settings which are scalars (globals or environment variables)',
	];

	/**
	 *
	 * @return int
	 */
	public function run(): int {
		$app = $this->application;
		$variables = $app->loader->variables();

		[$missingClasses, $warning_top_levels] = $this->collectMisnamedClassConfigurations();
		$variables['-missing-classes-'] = $missingClasses;
		$variables['-warning-top-'] = $warning_top_levels;

		$flags = $this->show_flags();

		$result = [];
		foreach ([
			'show_loaded' => ['Loaded', Configuration_Loader::PROCESSED],
			'show_not_loaded' => ['Missing', Configuration_Loader::MISSING],
			'show_skipped' => ['Skipped', Configuration_Loader::SKIPPED],
			'show_externals' => ['External variables', Configuration_Loader::EXTERNALS],
			'show_missing_classes' => ['Missing classes', '-missing-classes-'],
			'show_top_level_scalar' => ['Top-level variables which are scalar', '-warning-top-'],
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
	private function show_flags(): array {
		$flags = [
			'loaded' => true,
			'not_loaded' => true,
			'skipped' => true,
			'externals' => false,
			'missing_classes' => false,
			'top_level_scalar' => false,
		];
		foreach ($flags as $flag => $default) {
			if ($this->optionBool($flag)) {
				// if any value is true, return the actual values
				return ArrayTools::prefixKeys($this->options($flags), 'show_');
			}
		}
		// Show all
		return ArrayTools::prefixKeys($flags, 'show_');
	}

	/**
	 *
	 * @return
	 */
	public function collectMisnamedClassConfigurations(): array {
		$config = $this->application->configuration;
		$missing = $warning = [];
		foreach ($config as $key => $next) {
			if ($next instanceof Configuration) {
				try {
					if (!class_exists($key, true)) {
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
	private function outputList(array $list): array {
		return $list;
	}
}
