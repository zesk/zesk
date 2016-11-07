<?php

/**
 * Like a select, but uses Bootstrap dropdown
 * 
 * @author kent
 */
class Control_DropDown extends Control_Select {
	public function theme_variables() {
		$parent = $this->parent();
		$default_no_input_group = $parent ? $parent->option_bool("is_input_group") : false;
		return parent::theme_variables() + array(
			'no_input_group' => $this->option_bool('no_input_group', $default_no_input_group)
		);
	}
}
