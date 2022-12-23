<?php declare(strict_types=1);
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
	protected $css_paths = [
		'/share/chosen/chosen.css',
	];

	protected $javascript_paths = [
		'/share/chosen/chosen.jquery.js',
	];

	protected $javascript_settings_inherit = [
		'width' => null,
		'search_contains' => true,
		'disable_search_threshold' => 5,
		'include_group_label_in_selected' => true,
	];

	protected $jquery_ready = [];

	//"\$('.chosen-select').chosen(zesk.get_path('modules.chosen'));"
	protected static $jquery_ready_pattern = [
		'{selector}.chosen($.extend(zesk.get_path(\'modules.chosen\'),{json_options}));',
	];

	public function initialize(): void {
		parent::initialize();
		$locale = $this->application->locale;
		$this->javascript_settings_inherit['no_results_text'] = $locale->__('No results match');
		$classes = $this->optionIterable('hook_classes', Control_Select::class);
		$hooks = $this->application->hooks;
		$chosen = $this;
		$ready_pattern = self::$jquery_ready_pattern;
		foreach ($classes as $class) {
			$hooks->add("$class::render", function ($widget) use ($chosen, $ready_pattern): void {
				/* @var $widget Control_Select $widget  */
				if ($widget->optionBool('skip-chosen') || $widget->is_single()) {
					return;
				}
				$widget->addClass('chosen-select');
				$code = map($ready_pattern, [
					'selector' => $widget->jquery_target_expression(),
					'json_options' => ArrayTools::keysRemovePrefix($widget->option(ArrayTools::prefixKeys($chosen->javascript_settings(), 'chosen_')), 'chosen_'),
				]);
				$widget->response()
					->html()
					->jquery($code);
			});
		}
	}
}
