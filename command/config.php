<?php
namespace zesk;

/**
 * List configuration files which are examined and loaded for the application.
 *
 * @category Debugging
 * @author kent
 *
 */
class Command_Config extends Command_Base {
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
	protected $option_types = array(
		'loaded' => 'boolean',
		'not-loaded' => 'boolean',
		'skipped' => 'boolean',
		'externals' => 'boolean',
		'missing-classes' => 'boolean',
		'top-level-scalar' => 'boolean'
	);

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Command::run()
	 */
	public function run() {
		/* @var $zesk zesk\Kernel */
		$app = $this->application;
		$result = 0;
		$variables = $app->loader->variables();

		$loaded = $variables['processed'];
		$not_loaded = $variables['missing'];
		$externals = $variables['externals'];
		$skipped = $variables['skipped'];
		list($missing_vars, $warning_top_levels) = $this->collect_misnamed_class_configurations();

		$show_loaded = $show_not_loaded = $show_skipped = $show_externals = $show_missing_classes = $show_top_level_scalar = null;
		extract($this->show_flags(), EXTR_IF_EXISTS);

		if ($show_loaded) {
			echo $this->output_list("INFO: Loaded configuration files:", $loaded);
		}
		if ($show_not_loaded) {
			echo $this->output_list("NOTICE: Not loaded configuration files (file not found):", $not_loaded);
		}

		if ($show_skipped && count($skipped) > 0) {
			echo $this->output_list("ERROR: Skipped due to syntax errors:", $skipped);
		}
		if ($show_externals && count($externals) > 0) {
			echo $this->output_list("INFO: Dependency variables:", $externals);
		}
		if ($show_missing_classes && count($missing_vars) > 0) {
			sort($missing_vars);
			echo $this->output_list("WARNING: Variables have no corresponding class:", $missing_vars);
		}
		if ($show_top_level_scalar && count($warning_top_levels) > 0) {
			echo $this->output_list("NOTICE: Top-level variables which are scalar:", $warning_top_levels);
		}
		return 0;
	}
	private function show_flags() {
		$flags = array(
			'loaded' => true,
			'not_loaded' => true,
			'skipped' => true,
			'externals' => false,
			'missing_classes' => false,
			'top_level_scalar' => false
		);
		$result = array();
		foreach ($flags as $flag => $default) {
			if ($this->option_bool($flag)) {
				// if any value is true, return the actual values
				return ArrayTools::kprefix($this->option($flags), "show_");
			}
		}
		// Show all
		return ArrayTools::kprefix($flags, "show_");
	}
	/**
	 *
	 * @return unknown[][]|mixed[]
	 */
	public function collect_misnamed_class_configurations() {
		$config = $this->application->configuration;
		$missing = $warning = array();
		foreach ($config as $key => $next) {
			if ($next instanceof Configuration) {
				try {
					if (!class_exists($key, true)) {
						$missing[] = $key;
					}
				} catch (\zesk\Exception_Class_NotFound $e) {
					$missing[] = $key;
				}
			} else {
				$warning[] = $key;
			}
		}
		return array(
			$missing,
			$warning
		);
	}

	/**
	 *
	 * @param string $title
	 * @param array $list
	 * @return string
	 */
	private function output_list($title, array $list) {
		return $title . $this->sep . implode($this->sep, $list) . $this->suffix;
	}
}
