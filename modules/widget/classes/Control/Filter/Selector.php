<?php declare(strict_types=1);
namespace zesk;

/**
 * Add to a filter to enable selection of filters via a menu
 *
 * @see control/filter/selector.tpl
 * @author kent
 *
 */
class Control_Filter_Selector extends Control {
	public const option_toggle_mode = 'toggle_mode';

	public $filtered_children = null;

	protected $options = [
		'name' => 'filter-selector',
		'column' => 'filter-selector',
		'id' => 'filter-selector',
	];

	/**
	 * When toggle mode is enabled, filters are either all on or all off
	 *
	 * Simplification for novice users
	 *
	 * @param boolean $set
	 * @retrn boolean|Control_Filter_Selector
	 */
	public function toggle_mode($set = null) {
		return ($set === null) ? $this->optionBool(self::option_toggle_mode) : $this->setOption(self::option_toggle_mode, toBool($set));
	}

	private function filtered_children() {
		if (is_array($this->filtered_children)) {
			return $this->filtered_children;
		}
		$children = $this->parent()->children();
		unset($children[$this->column()]);
		return $this->filtered_children = $children;
	}

	public function is_visible() {
		$children = $this->filtered_children();
		if (count($children) === 0) {
			return false;
		}
		return true;
	}

	public function themeVariables(): array {
		return [
			'toggle_mode' => $this->toggle_mode(),
			'widgets' => $this->filtered_children(),
		] + parent::themeVariables();
	}
}
