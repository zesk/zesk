<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage default
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
namespace zesk;

class Control_Color extends Control_Text {
	public function initialize(): void {
		$this->set_option("show_size", 7, false);
		parent::initialize();
	}

	public function validate() {
		$this->set_option("id", $this->name());

		$name = $this->column();
		$color = $this->value();
		if (begins($color, "#")) {
			$color = substr($color, 1);
			$this->value($color);
		}
		return parent::validate();
	}

	public function targets(array $set = null) {
		return $set === null ? $this->option_array('targets', []) : $this->set_option('targets', $set);
	}
}
