<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

class Control_Pairs extends Control {
	protected $options = [
		'default' => [],
	];

	public function validate() {
		return true;
	}

	private function _from_request() {
		$col = $this->column();
		$names = $this->request->geta($col);
		$values = $this->request->geta($col . '_value');
		$result = [];
		foreach ($names as $k => $name) {
			$value = avalue($values, $k);
			if (!empty($name) || !empty($value)) {
				$result[$name] = $value;
			}
		}
		return $result;
	}

	public function load(): void {
		$result = $this->_from_request();
		$this->value($result);
	}
}
