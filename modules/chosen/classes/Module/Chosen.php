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
        '/share/chosen/chosen.css',
    );

    protected $javascript_paths = array(
        '/share/chosen/chosen.jquery.js',
    );

    protected $javascript_settings_inherit = array(
        "width" => null,
        "search_contains" => true,
        "disable_search_threshold" => 5,
        "include_group_label_in_selected" => true,
    );

    protected $jquery_ready = array();
    
    //"\$('.chosen-select').chosen(zesk.get_path('modules.chosen'));"
    protected static $jquery_ready_pattern = array(
        "{selector}.chosen(\$.extend(zesk.get_path('modules.chosen'),{json_options}));",
    );

    public function initialize() {
        parent::initialize();
        $locale = $this->application->locale;
        $this->javascript_settings_inherit['no_results_text'] = $locale->__('No results match');
        $classes = $this->option_list("hook_classes", Control_Select::class);
        $hooks = $this->application->hooks;
        $chosen = $this;
        $ready_pattern = self::$jquery_ready_pattern;
        foreach ($classes as $class) {
            $hooks->add("$class::render", function (Widget $widget) use ($chosen, $ready_pattern) {
                if ($widget->option_bool("skip-chosen") || $widget->is_single()) {
                    return;
                }
                $widget->add_class("chosen-select");
                $code = map($ready_pattern, array(
                    "selector" => $widget->jquery_target_expression(),
                    "json_options" => ArrayTools::kunprefix($widget->option(ArrayTools::kprefix($chosen->javascript_settings(), "chosen_")), "chosen_"),
                ));
                $widget->response()
                    ->html()
                    ->jquery($code);
            });
        }
    }
}
