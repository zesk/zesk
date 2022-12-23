<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage widgets
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 * Created on Wed Aug 27 22:23:05 EDT 2008 22:23:05
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Control_Text extends Control {
	protected function initialize(): void {
		$this->options += ArrayTools::keysMap($this->options, [
			'integer_minimum' => 'value_minimum',
			'integer_minimum_error' => 'value_minimum_error',
			'integer_maximum' => 'value_maximum',
			'integer_maximum_error' => 'value_maximum_error',
		]);
		parent::initialize();
	}

	public function value_minimum($set = null) {
		return $set === null ? $this->optionFloat('value_minimum') : $this->setOption('value_minimum', floatval($set));
	}

	public function value_maximum($set = null) {
		return $set === null ? $this->optionFloat('value_maximum') : $this->setOption('value_maximum', floatval($set));
	}

	private function _validate_numeric(&$v) {
		$locale = $this->application->locale;
		$clamp = toBool($this->optionBool('value_clamp'));
		$min = $this->optionInt('value_minimum', null);
		$max = $this->optionInt('value_maximum', null);
		if ($min !== null && $v < $min) {
			if ($clamp) {
				$v = $min;
			} else {
				$this->error($this->option('value_minimum_error', $locale->__('{label} must be at least {value_minimum}.')));
				return false;
			}
		}
		if ($max !== null && $v > $max) {
			if ($clamp) {
				$v = $max;
			} else {
				$this->error($this->option('value_maximum_error', $locale->__('{label} must be at most {value_maximum}')));
				return false;
			}
		}
		return true;
	}

	/*
	 * Validate an integer
	 */
	public function validate_integer() {
		$v = $this->value();
		if (empty($v)) {
			return !$this->required();
		}
		if (!is_numeric($v)) {
			$this->error($this->application->locale->__('You must enter a numeric value for {label}.'));
			return false;
		}
		if (!$this->_validate_numeric($v)) {
			return false;
		}
		$this->value(intval($v));
		return true;
	}

	public function validate_real() {
		$v = $this->value();
		if (empty($v)) {
			return !$this->required();
		}
		if (!empty($v) && !is_numeric($v)) {
			$this->error($this->application->locale->__('You must enter a numeric value for {label}.'));
			return false;
		}
		$v = floatval($v);
		if ($this->hasOption('decimal_precision')) {
			$v = round($v, $this->optionInt('decimal_precision'));
		}
		if (!$this->_validate_numeric($v)) {
			return false;
		}
		$this->value($v);
		return true;
	}

	public function themeVariables(): array {
		return [
			'placeholder' => $this->option('placeholder', $this->label),
			'password' => $this->optionBool('password'),
			'textarea' => $this->textarea(),
			'rows' => $this->optionInt('rows', 3),
			'cols' => $this->optionInt('cols', 60),
			'input_group_addon' => $this->option('input_group_addon', null),
		] + parent::themeVariables();
	}

	protected function validate(): bool {
		if (!parent::validate()) {
			return false;
		}
		if ($this->hasOption('validate')) {
			$validate = $this->option('validate');
			$method = "validate_$validate";
			if (!method_exists($this, $method)) {
				throw new Exception_Semantics("Unknokn validation method $method for Control_Text");
			}
			$result = $this->$method();
			if (!$result) {
				return $result;
			}
		}
		return $this->validate_size();
	}

	public function input_group_addon($set = null, $left = false) {
		if ($set !== null) {
			if ($left) {
				$this->setOption('input_group_addon_left', true);
			} else {
				$this->setOption('input_group_addon_left', null);
			}
			$this->setOption('input_group_addon', $set);
			return $this;
		}
		return $this->option('input_group_addon');
	}

	public function textarea($set = null) {
		if ($set !== null) {
			$this->setOption('textarea', $set);
			return $this;
		}
		return $this->optionBool('textarea');
	}

	public function rows($set = null) {
		if ($set !== null) {
			$this->textarea(true);
			return $this->setOption('rows', $set);
		}
		return $this->optionInt('rows', null);
	}
}
