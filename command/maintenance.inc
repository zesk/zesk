<?php

/**
 * Turn maintenance on or off
 * 
 * @author kent
 *
 */
class Command_Maintenance extends Command_Base {
	protected function initialize() {
		$this->application->hooks->add("Application::maintenance_context", array(
			$this,
			"maintenance_context"
		));
	}
	function run() {
		if (!$this->has_arg()) {
			echo $this->application->maintenance();
			return 0;
		}
		$arg = $this->get_arg("value");
		$this->message = $arg;
		$bool = to_bool($arg, null);
		if ($bool === null) {
			$this->application->maintenance(true);
			$this->log("Maintenance enabled with message \"$arg\"", array(
				"arg" => $arg
			));
		} else {
			$this->application->maintenance($bool);
			$this->log("Maintenance " . ($bool ? "enabled" : "disabled"));
		}
	}
	
	/**
	 * Pass values to store as part of the system globals upon maintenance
	 * 
	 * @param Application $app
	 * @param array $values
	 * @return array
	 */
	function maintenance_context(Application $app, array $values) {
		if (is_string($this->message)) {
			$values['Command_Maintenance::message'] = $this->message;
		}
		return $values;
	}
}
