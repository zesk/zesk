<?php declare(strict_types=1);
namespace zesk;

/**
 * Like a select, but uses Bootstrap dropdown
 *
 * @author kent
 */
class Control_Dropdown extends Control_Select {
	protected $options = [
		'skip-chosen' => true,
	];

	public function theme_variables() {
		$parent = $this->parent();
		$default_no_input_group = $parent ? $parent->option_bool("is_input_group") : false;
		return parent::theme_variables() + [
			'no_input_group' => $this->option_bool('no_input_group', $default_no_input_group),
		];
	}
}
