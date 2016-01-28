<?php

class Control_Help_Placement extends Control_Select {
	protected function initialize() {
		$this->control_options(array(
			'auto' => __('Automatic'),
			'top' => __('Top'),
			'bottom' => __('Bottom'),
			'left' => __('Left'),
			'right' => __('Right'),
			'auto top' => __('Automatic (Prefer Top)'),
			'auto bottom' => __('Automatic (Prefer Bottom)'),
			'auto left' => __('Automatic (Prefer Left)'),
			'auto right' => __('Automatic (Prefer Right)')
		));
		$this->required(true);
		$this->default_value('auto');
		parent::initialize();
	}
}