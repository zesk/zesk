<?php
namespace zesk;

/**
 * Cache commands. Currently takes a single argument: "clear"
 * 
 * @category Management
 * @author kent
 *
 */
class Command_Cache extends Command_Base {
	protected $option_types = array(
		"*" => "string"
	);
	protected function run() {
		if ($this->has_arg()) {
			do {
				$arg = $this->get_arg("command");
				$this->run_arg($arg);
			} while ($this->has_arg());
		} else {
			$this->run_arg("print");
		}
		return $this->has_errors() ? 1 : 0;
	}
	protected function run_arg($arg) {
		$method = "_exec_$arg";
		if (method_exists($this, $method)) {
			$this->$method();
		} else {
			$this->error("No such command {arg}", array(
				"arg" => $arg
			));
		}
	}
	private function _exec_clear() {
		$this->log("Running: Application::cache_clear");
		$this->application->cache_clear();
	}
}
