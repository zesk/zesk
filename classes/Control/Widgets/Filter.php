<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/Control/Widgets/Filter.php $
 * @package zesk
 * @subpackage control
 * @author kent
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
namespace zesk;

class Control_Widgets_Filter extends Control_Widgets {

	/**
	 * Filter control
	 * @var Control_Filter
	 */
	protected $filter = null;

	protected function hook_filters() {
		return array();
	}

	protected function initialize_filter() {
		if ($this->filter === null) {
			$filters = $this->call_hook("filters");
			if (count($filters) > 0) {
				$options = $this->options_include("URI;filter_preserve_include;filter_preserve_exclude;ajax_id;filter_form_id");
				$options = arr::map_keys($options, array(
					"filter_form_id" => "form_id"
				));
				$options['id'] = $options['column'] = "filter";
				$this->filter = new Control_Filter($options);
				$this->filter->children($filters);
				$this->filter->wrap("div", ".filters");
				$this->child($this->filter);

				$this->call_hook("initialize_filter");
			}
		}
	}

	protected function initialize() {
		$this->initialize_filter();
		parent::initialize();
	}

	function filter() {
		return $this->filter;
	}

	public function show_filter($set = null) {
		return $set !== null ? $this->set_option('show_filter', to_bool($set)) : $this->option_bool('show_filter', true);
	}

	public function theme_variables() {
		return array(
			'filter' => $this->filter,
			'show_filter' => $this->show_filter()
		) + parent::theme_variables();
	}
}
