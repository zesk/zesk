<?php
/**
 * 
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class Module_Chosen extends Module_JSLib {
	protected $css_paths = array(
		'/share/chosen/chosen.css'
	);
	protected $javascript_paths = array(
		'/share/chosen/chosen.jquery.js'
	);
	protected $javascript_settings_inherit = array(
		"width" => null,
		"search_contains" => true,
		"disable_search_threshold" => 5,
		"include_group_label_in_selected" => true
	);
	protected $jquery_ready = array();
	
	//"\$('.chosen-select').chosen(zesk.get_path('modules.chosen'));"
	protected static $jquery_ready_pattern = array(
		"{selector}.chosen(\$.extend(zesk.get_path('modules.chosen'),{json_options}));"
	);
	public function initialize() {
		parent::initialize();
		$this->javascript_settings_inherit['no_results_text'] = __('No results match');
		$classes = $this->option_list("hook_classes", "zesk\\Control_Select");
		$hooks = $this->application->hooks;
		foreach ($classes as $class) {
			$chosen = $this;
			$hooks->add("$class::initialized", function (Control_Select $widget) use ($chosen) {
				if ($widget->option_bool("skip-chosen") || $widget->is_single()) {
					return;
				}
				$widget->add_class("chosen-select");
				$widget->response()->jquery(map(self::$jquery_ready_pattern, array(
					"selector" => $widget->jquery_target_expression(),
					"json_options" => arr::kunprefix($widget->option(arr::kprefix($chosen->javascript_settings(), "chosen_")), "chosen_")
				)));
			});
		}
	}
}
