<?php
namespace zesk;

/**
 * One-step deployment for applications
 *
 * @author kent
 * @category Management
 */
class Command_Build extends Command_Base {
	protected $option_types = array(
		'no-update' => 'boolean',
		'no-cache-clear' => 'boolean'
	);
	protected $option_help = array(
		'no-update' => 'Skip updating of all modules',
		'no-cache-clear' => 'Skip clearing the cache'
	);
	function build_hook_callback($callable, array $arguments) {
		$this->log("Running build step {callable}", array(
			"callable" => $this->application->hooks->callable_string($callable)
		));
	}
	function build_result_callback($callable, $previous_result, $new_result) {
		$callable_string = $this->application->hooks->callable_string($callable);
		$this->log("Completed build step {callable}", array(
			"callable" => $callable_string
		));
		if (is_array($new_result)) {
		}
	}
	function run() {
		/*
		 * Ok, so what do we do when we build an app?
		 *
		 * 1. Do updates
		 */
		$this->run_cache_clear();
		$this->run_update();
		$this->run_build_hooks();
	}
	private function run_cache_clear() {
		if (!$this->option_bool('no-cache-clear')) {
			$this->log("Clearing cache ...");
			$this->log($this->zesk_cli("cache clear"));
		} else {
			$this->log("Skipping clearing cache ...");
		}
	}
	private function run_update() {
		if (!$this->option_bool('no-update')) {
			$this->log("Running module updates ...");
			$this->log($this->zesk_cli("update"));
		} else {
			$this->log("Skipping module updates ...");
		}
	}
	private function run_build_hooks() {
		$this->application->modules->all_hook_arguments("build", array(
			$this
		), true, array(
			$this,
			"build_hook_callback"
		), array(
			$this,
			"build_result_callback"
		));
	}
}
