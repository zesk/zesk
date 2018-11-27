<?php
namespace zesk;

class Control_Data extends Control {
	protected $options = array(
		"default" => array(),
	);

	public function validate() {
		return true;
	}

	public function option_merge($set = null) {
		return $set === null ? $this->option_bool("merge") : $this->set_option('merge', to_bool($set));
	}

	public function allow_keys($set = null) {
		return $set === null ? $this->option_list("allow_keys") : $this->set_option('allow_keys', to_list($set));
	}

	public function load() {
		$column = $this->column();
		$current_value = $this->value();
		if (!is_array($current_value)) {
			$current_value = array();
		}
		$value = $this->request->geta($column);
		if (is_array($value)) {
			if ($this->has_option("allow_keys")) {
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
