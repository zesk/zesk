<?php
namespace zesk\Test;

use zesk\Options;
use zesk\Exception_Semantics;

class Method extends Options {

	/**
	 *
	 * @var \zesk\Test
	 */
	public $test = null;

	/**
	 *
	 * @var string[]
	 */
	private $depends = array();
	/**
	 *
	 * @var string
	 */
	private $name = "";

	/**
	 *
	 * @var string
	 */
	private $data_provider_method = null;
	/**
	 *
	 * @param Test $test
	 * @param string $method
	 * @param array $options
	 */
	function __construct(\zesk\Test $test, $name, array $options = array()) {
		parent::__construct($options);
		$this->test = $test;
		$this->name = $name;
		$this->data_provider_method = $this->option("dataProvider", $this->option('data_provider', null));
		if ($this->data_provider_method) {
			if (!method_exists($test, $this->data_provider_method)) {
				throw new Exception_Semantics("No such data provider method {method} exists to run test {name}", array(
					"method" => $this->data_provider_method,
					"name" => $this->name
				));
			}
		}
		$this->depends = $this->option_list("depends");
		if (count($this->depends) > 0) {
			if ($this->data_provider_method) {
				throw new Exception_Semantics("@dataProvider {method} and @depends {depends} specified in test {name}", array(
					"method" => $this->data_provider_method,
					"depends" => $this->depends,
					"name" => $this->name
				));
			}
		}
	}
	function name() {
		return $this->name;
	}
	function has_dependencies() {
		return count($this->depends) > 0;
	}
	function dependencies_have_been_met() {
		if (!$this->has_dependencies()) {
			return true;
		}
		foreach ($this->depends as $depend) {
			if (!$this->test->has_test_result($depend)) {
				return false;
			}
		}
		return true;
	}
	function dependencies_can_be_met() {
		if (!$this->has_dependencies()) {
			return true;
		}
		foreach ($this->depends as $depend) {
			if (!$this->test->has_test_result($depend)) {
				if ($this->test->is_test_queued($depend)) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 *
	 * @return array
	 */
	private function _compute_data_provider() {
		if ($this->data_provider_method) {
			$data_provider = call_user_func_array(array(
				$this->test,
				$this->data_provider_method
			), array());
			return $data_provider;
		}
		if ($this->has_dependencies()) {
			$arguments = array();
			foreach ($this->depends as $depend) {
				$arguments = $this->test->get_test_result($depend);
			}
			return array(
				$arguments
			);
		}
		return null;
	}
	public function can_run() {
		if ($this->has_dependencies()) {
			return $this->dependencies_have_been_met();
		}
		return true;
	}

	/**
	 *
	 * @return string[string]
	 */
	public function variables() {
		return array(
			"depends" => $this->depends,
			"name" => $this->name
		);
	}
	/**
	 *
	 * @return boolean|NULL[][]|mixed|NULL
	 */
	public function run() {
		if (!$this->can_run()) {
			throw new Exception("Dependencies {depends} are not met for test {name}", $this->variables());
		}
		$data_provider = $this->_compute_data_provider();
		if (is_array($data_provider) && count($data_provider) === 1) {
			$arguments = first($data_provider);
			if (!is_array($arguments)) {
				$arguments = array(
					$arguments
				);
			}
			$this->run_test_method_single($arguments);
		} else if (is_array($data_provider)) {
			$this->run_test_method_data_provider($data_provider);
		} else {
			$this->run_test_method_single(array());
		}
	}

	/**
	 *
	 * @param unknown $test
	 * @param array $settings
	 * @param unknown $data_provider
	 */
	private function run_test_method_single(array $arguments) {
		$this->test->_run_test_method($this, $arguments);
	}

	/**
	 *
	 * @param unknown $data_provider
	 */
	private function run_test_method_data_provider($data_provider) {
		$loop = 0;
		$test_output = "";
		$name = $this->name;
		foreach ($data_provider as $arguments) {
			if (!is_array($arguments)) {
				$arguments = array(
					$arguments
				);
			}
			$this->test->log(__("- $name iteration {0}: {1}", $loop + 1, substr(json_encode($arguments), 0, 80)));
			$this->test->_run_test_method($this, $arguments);
			$test_output .= $this->test->last_test_output();
			$loop++;
		}
		$this->test->last_test_output($test_output);
	}
}