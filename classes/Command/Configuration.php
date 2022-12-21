<?php declare(strict_types=1);
namespace zesk;

/**
 * List configuration files which are examined and loaded for the application.
 *
 * @category Debugging
 * @author kent
 *
 */
class Command_Config extends Command_Base {
	protected array $shortcuts = ['conf', 'config', 'configuration'];

	/**
	 *
	 * @var string
	 */
	private $sep = "\n\t";

	/**
	 *
	 * @var string
	 */
	private $suffix = "\n\n";

	protected array $option_types = [
		'loaded' => 'boolean',
		'not-loaded' => 'boolean',
		'skipped' => 'boolean',
		'externals' => 'boolean',
		'missing-classes' => 'boolean',
		'top-level-scalar' => 'boolean',
	];

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Command::run()
	 */
	public function run(): int {
		$app = $this->application;
		$variables = $app->loader->variables();

		$loaded = $variables[Configuration_Loader::PROCESSED];
		$not_loaded = $variables[Configuration_Loader::MISSING];
		$externals = $variables[Configuration_Loader::EXTERNALS];
		$skipped = $variables[Configuration_Loader::SKIPPED];
		[$missing_vars, $warning_top_levels] = $this->collect_misnamed_class_configurations();

		$show_loaded = $show_not_loaded = $show_skipped = $show_externals = $show_missing_classes = $show_top_level_scalar = null;
		extract($this->show_flags(), EXTR_IF_EXISTS);

		if ($show_loaded) {
			echo $this->output_list('INFO: Loaded configuration files:', $loaded);
		}
		if ($show_not_loaded) {
			echo $this->output_list('NOTICE: Not loaded configuration files (file not found):', $not_loaded);
		}

		if ($show_skipped && count($skipped) > 0) {
			echo $this->output_list('ERROR: Skipped due to syntax errors:', $skipped);
		}
		if ($show_externals && count($externals) > 0) {
			echo $this->output_list('INFO: Dependency variables:', $externals);
		}
		if ($show_missing_classes && count($missing_vars) > 0) {
			sort($missing_vars);
			echo $this->output_list('WARNING: Variables have no corresponding class:', $missing_vars);
		}
		if ($show_top_level_scalar && count($warning_top_levels) > 0) {
			echo $this->output_list('NOTICE: Top-level variables which are scalar:', $warning_top_levels);
		}
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
		$result = [];
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
	public function collect_misnamed_class_configurations(): array {
		$config = $this->application->configuration;
		$missing = $warning = [];
		foreach ($config as $key => $next) {
			if ($next instanceof Configuration) {
				try {
					if (!class_exists($key, true)) {
						$missing[] = $key;
					}
				} catch (Exception_Class_NotFound $e) {
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
	 *
	 * @param string $title
	 * @param array $list
	 * @return string
	 */
	private function output_list(string $title, array $list): string {
		return $title . $this->sep . implode($this->sep, $list) . $this->suffix;
	}
}
