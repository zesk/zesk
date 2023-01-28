<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage default
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk;

class Control_Color extends Control_Text {
	public function initialize(): void {
		$this->setOption('show_size', 7, false);
		parent::initialize();
	}

	public function validate(): bool {
		$this->setOption('id', $this->name());

		$name = $this->column();
		$color = $this->value();
		if (str_starts_with($color, '#')) {
			$color = substr($color, 1);
			$this->value($color);
		}
		return parent::validate();
	}

	public function targets(array $set = null) {
		return $set === null ? $this->optionArray('targets', []) : $this->setOption('targets', $set);
	}
}
