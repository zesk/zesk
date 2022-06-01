<?php declare(strict_types=1);
namespace zesk;

class Control_Data extends Control {
	protected $options = [
		'default' => [],
	];

	public function validate() {
		return true;
	}

	public function option_merge($set = null) {
		return $set === null ? $this->optionBool('merge') : $this->setOption('merge', toBool($set));
	}

	public function allow_keys($set = null) {
		return $set === null ? $this->optionIterable('allow_keys') : $this->setOption('allow_keys', to_list($set));
	}

	public function load(): void {
		$column = $this->column();
		$current_value = $this->value();
		if (!is_array($current_value)) {
			$current_value = [];
		}
		$value = $this->request->getArray($column);
		if (is_array($value)) {
			if ($this->hasOption('allow_keys')) {
				$value = ArrayTools::filter($value, $this->allow_keys());
			}
			if (count($value) > 0) {
				if ($this->option_merge()) {
					$value = $value + $current_value;
				}
				$this->value($value);
			}
		}
	}
}
