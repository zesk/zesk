<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage default
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk;

class Control_Hidden extends Control {
	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Widget::is_visible()
	 */
	public function is_visible(): bool {
		return false;
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Widget::render()
	 */
	public function render(): string {
		return HTML::hidden($this->name(), $this->value(), $this->inputAttributes() + $this->dataAttributes());
	}
}
