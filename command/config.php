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
	public function run() {
		/* @var $zesk zesk\Kernel */
		$app = $this->application;
		$variables = $app->loader->variables();
		$loaded = $variables['processed'];
		$not_loaded = $variables['missing'];
		$skipped = $variables['skipped'];
		$externals = $variables['externals'];
		$sep = "\n\t";
		$suffix = "\n\n";
		echo "Loaded configuration files:" . $sep . implode($sep, $loaded) . $suffix;
		echo "Not loaded configuration files (file not found):" . $sep . implode($sep, $not_loaded) . $suffix;
		if (count($externals) > 0) {
			echo "Dependency variables:" . $sep . implode($sep, $skipped) . $suffix;
		}
		if (count($skipped) > 0) {
			echo "ERROR Skipped due to syntax errors:" . $sep . implode($sep, $skipped) . $suffix;
		}
		return 0;
	}
}
