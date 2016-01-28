<?php
class Control_Spectrum extends Control {

	/**
	 * theme name
	 *
	 * @var string
	 */
	protected $theme = "control/spectrum";

	/**
	 * (non-PHPdoc)
	 *
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

