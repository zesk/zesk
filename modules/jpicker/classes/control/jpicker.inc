<?php

class Control_jPicker extends Control {

	/**
	 * theme name
	 *
	 * @var string
	 */
	protected $theme = "control/jpicker";

	/**
	 * (non-PHPdoc)
	 * @see Widget::validate()
	 */
	function validate() {
		$this->set_option("id", $this->name(), false);
		$color = $this->value();
		if (begins($color, "#")) {
			$color = substr($color, 1);
			$this->value($color);
		}
		return parent::validate();
	}
}

