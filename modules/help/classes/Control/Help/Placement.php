<?php
namespace zesk;

class Control_Help_Placement extends Control_Select {
	protected function initialize() {
		$this->control_options($this->locale->__(array(
			'auto' => 'Automatic',
			'top' => 'Top',
			'bottom' => 'Bottom',
			'left' => 'Left',
			'right' => 'Right',
			'auto top' => 'Automatic (Prefer Top)',
			'auto bottom' => 'Automatic (Prefer Bottom)',
			'auto left' => 'Automatic (Prefer Left)',
			'auto right' => 'Automatic (Prefer Right)',
		)));
		$this->required(true);
		$this->default_value('auto');
		parent::initialize();
	}
}
