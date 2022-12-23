<?php
declare(strict_types=1);

namespace zesk\Test;

use zesk\Options;
use zesk\Text;
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
	private $depends = [];

	/**
	 *
	 * @var string
	 */
	private $name = '';

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
	public function __construct(\zesk\Test $test, $name, array $options = []) {
		parent::__construct($options);
		$this->test = $test;
		$this->name = $name;
		$this->data_provider_method = $this->option('dataProvider', $this->option('data_provider', null));
		if ($this->data_provider_method) {
			if (!method_exists($test, $this->data_provider_method)) {
				throw new Exception_Semantics('No such data provider method {method} exists to run test {name}', [
					'method' => $this->data_provider_method,
					'name' => $this->name,
				]);
			}
		}
		$this->depends = $this->optionIterable('depends');
		if (count($this->depends) > 0) {
			if ($this->data_provider_method) {
				throw new Exception_Semantics('@dataProvider {method} and @depends {depends} specified in test {name}', [
					'method' => $this->data_provider_method,
					'depends' => $this->depends,
					'name' => $this->name,
				]);
			}
		}
	}

	public function name() {
		return $this->name;
	}

	public function has_dependencies() {
		return count($this->depends) > 0;
	}

	public function dependencies_have_been_met() {
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

	public function dependencies_can_be_met() {
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
			$data_provider = call_user_func_array([
				$this->test,
				$this->data_provider_method,
			], []);
			return $data_provider;
		}
		if ($this->has_dependencies()) {
			$arguments = [];
			foreach ($this->depends as $depend) {
				$arguments = $this->test->get_test_result($depend);
			}
			return [
				$arguments,
			];
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
	public function variables(): array {
		return [
			'depends' => $this->depends,
			'name' => $this->name,
		];
	}

	/**
	 *
	 * @return boolean|NULL[][]|mixed|NULL
	 */
	public function run() {
		if (!$this->can_run()) {
			throw new Exception('Dependencies {depends} are not met for test {name}', $this->variables());
		}
		$data_provider = $this->_compute_data_provider();
		if (is_array($data_provider) && count($data_provider) === 1) {
			$arguments = first($data_provider);
			if (!is_array($arguments)) {
				$arguments = [
					$arguments,
				];
			}
			$this->run_test_method_single($arguments);
		} elseif (is_array($data_provider)) {
			$this->run_test_method_data_provider($data_provider);
		} else {
			$this->run_test_method_single([]);
		}
	}

	/**
	 *
	 * @param unknown $test
	 * @param array $settings
	 * @param unknown $data_provider
	 */
	private function run_test_method_single(array $arguments): void {
		$this->test->_run_test_method($this, $arguments);
	}

	/**
	 *
	 * @param unknown $data_provider
	 */
	private function run_test_method_data_provider(array $data_provider): void {
		$loop = 0;
		$test_output = '';
		$name = $this->name;
		foreach ($data_provider as $arguments) {
			if (!is_array($arguments)) {
				$this->test->log('Arguments is not an array - converting to single argument {type}', [
					'type' => type($arguments),
				]);
				$arguments = [
					$arguments,
				];
			}
			$log_line = map("- $name iteration {0}: {1}", [
				strval($loop + 1),
				substr(strval(json_encode($arguments)), 0, 80),
			]);
			$result = $this->test->_run_test_method($this, $arguments);
			$this->test->log(Text::leftAlign($log_line, 80, ' ', true) . ' : ' . ($result ? 'OK' : 'FAIL'));
			$last_test = $this->test->last_test_output();
			if ($last_test) {
				$this->test->log($last_test, ['severity' => 'error']);
				$test_output .= $last_test;
			}
			$loop++;
		}
		$this->test->last_test_output($test_output);
	}
}
