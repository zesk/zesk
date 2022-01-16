<?php declare(strict_types=1);
namespace zesk;

/**
 * One-step deployment for applications
 *
 * @author kent
 * @category Management
 */
class Command_Build extends Command_Base {
	protected array $option_types = [
		'no-update' => 'boolean',
		'no-cache-clear' => 'boolean',
	];

	protected array $option_help = [
		'no-update' => 'Skip updating of all modules',
		'no-cache-clear' => 'Skip clearing the cache',
	];

	public function build_hook_callback($callable, array $arguments): void {
		$this->log("Running build step {callable}", [
			"callable" => $this->application->hooks->callable_string($callable),
		]);
	}

	public function build_result_callback($callable, $previous_result, $new_result): void {
		$callable_string = $this->application->hooks->callable_string($callable);
		$this->log("Completed build step {callable}", [
			"callable" => $callable_string,
		]);
		if (is_array($new_result)) {
		}
	}

	public function run(): void {
		/*
		 * Ok, so what do we do when we build an app?
		 *
		 * 1. Do updates
		 */
		$this->run_cache_clear();
		$this->run_update();
		$this->run_build_hooks();
	}

	private function run_cache_clear(): void {
		if (!$this->optionBool('no-cache-clear')) {
			$this->log("Clearing cache ...");
			$this->log($this->zesk_cli("cache clear"));
		} else {
			$this->log("Skipping clearing cache ...");
		}
	}

	private function run_update(): void {
		if (!$this->optionBool('no-update')) {
			$this->log("Running module updates ...");
			$this->log($this->zesk_cli("update"));
		} else {
			$this->log("Skipping module updates ...");
		}
	}

	private function run_build_hooks(): void {
		$this->application->modules->all_hook_arguments("build", [
			$this,
		], true, [
			$this,
			"build_hook_callback",
		], [
			$this,
			"build_result_callback",
		]);
	}
}
