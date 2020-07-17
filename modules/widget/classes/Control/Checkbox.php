<?php
/**
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 *            Created on Tue Jul 15 16:38:32 EDT 2008
 */
namespace zesk;

class Control_Checkbox extends Control {
	private function apply_options($bool_result) {
		if ($bool_result) {
			if ($this->has_option("trueoptions")) {
				$this->set_option($this->first_option("options_true;trueoptions"));
			}
		} else {
			if ($this->has_option("falseoptions")) {
				$this->set_option($this->first_option("options_false;falseoptions"));
			}
		}
	}

	private function input_value_true() {
		return $this->input_value("input_value_true", true);
	}

	private function object_value_true() {
		return $this->object_value("value_true;truevalue", true);
	}

	private function object_value_false() {
		return $this->object_value("value_false;falsevalue", false);
	}

	private function input_value($names, $default) {
		$value = $this->first_option($names, $default);
		if (is_string($value)) {
			return $this->object->apply_map($value);
		}
		return strval($value);
	}

	private function object_value($names, $default) {
		$value = $this->first_option($names, $default);
		return $this->object->apply_map($value);
	}

	public function checked() {
		if ($this->has_option("checked")) {
			return $this->option_bool("checked");
		}
		return $this->value() === $this->object_value_true();
	}

	public function submitted() {
		return $this->request->getb($this->name() . "_ckbx");
	}

	public function load() {
		$name = $this->name();
		if ($this->request->has($name) || $this->request->has($name . "_ckbx")) {
			$new_value = strval(to_bool($this->request->get($name)));
			$checked = ($new_value === $this->input_value_true());
			$object_value = $checked ? $this->object_value_true() : $this->object_value_false();
			$this->value($object_value);
			if ($this->option_bool("debug_load")) {
				$this->application->logger->warning("Set widget {name} to {value} ({type})", array(
					"name" => $this->name(),
					"value" => $object_value,
					"type" => type($object_value),
				));
			}
			$this->apply_options($checked);
		}
	}

	public function validate() {
		$cont_name = $this->name() . "_sv";
		if ($this->request->getb($cont_name)) {
			$this->message($this->option("continue_message", $this->application->locale->__("Form was updated.")));
			return false;
		}
		return true;
	}

	public function label_checkbox($value = null) {
		if ($value === null) {
			return $this->option('label_checkbox');
		}
		return $this->set_option('label_checkbox', $value);
	}

	public function checked_value($value = null) {
		if ($value === null) {
			return $this->option('checked_value', 1);
		}
		return $this->set_option('checked_value', $value);
	}

	public function theme_variables() {
		$this->apply_options($this->checked());
		return parent::theme_variables() + array(
			'checked' => $this->checked(),
			'checked_value' => $this->object->apply_map($this->checked_value()),
		);
	}

	/**
	 * Return the jQuery expression to determine the value of this widget
	 *
	 * @return string
	 */
	public function jquery_value_expression() {
		$id = $this->id();
		return "\$(\"#$id\").prop(\"checked\")";
	}
}
