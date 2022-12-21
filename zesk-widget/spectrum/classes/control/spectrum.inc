<?php declare(strict_types=1);
namespace zesk;

class Control_Spectrum extends Control {
	/**
	 * (non-PHPdoc)
	 *
	 * @see Widget::validate()
	 */
	public function validate(): bool {
		$this->setOption('id', $this->name(), false);

		$color = $this->value();
		if (str_starts_with($color, '#')) {
			$color = substr($color, 1);
			$this->value($color);
		}
		return parent::validate();
	}
}
