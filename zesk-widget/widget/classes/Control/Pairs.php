<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

class Control_Pairs extends Control {
	protected $options = [
		'default' => [],
	];

	public function validate(): bool {
		return true;
	}

	private function _from_request() {
		$col = $this->column();
		$names = $this->request->getList($col);
		$values = $this->request->getList($col . '_value');
		$result = [];
		foreach ($names as $k => $name) {
			$value = $values[$k] ?? null;
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
