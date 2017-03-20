<?php
use zesk\HTML;
use zesk\Timestamp;

/* @var $this \zesk\Template */
/* @var $zesk \zesk\Kernel */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response_Text_HTML */
/* @var $current_user \User */
/* @var $module_class string */
$settings = Settings::instance();

$now = Timestamp::now("UTC");

foreach (array(
	"minute" => __("Last minute run"),
	"hour" => __("Last hour run"),
	"week" => __("Last week run"),
	"month" => __("Last month run"),
	"year" => __("Last year run")
) as $unit => $label) {
	$suffix = "_$unit";
	$runtime = $settings->get($module_class . '::last' . $suffix);
	$attributes = array();
	if (!$runtime) {
		$value = __("Never");
		$attributes['class'] = 'error';
	} else {
		$last_run = Timestamp::factory($runtime, "UTC");
		$before_error = $now->duplicate()->add_unit($unit, -1);
		if ($last_run->before($before_error)) {
			$attributes['class'] = 'error';
			$value = $last_run->format("{delta}");
		} else {
			$value = $last_run->format("{delta}");
		}
	}
	$items[] = HTML::tag('li', $attributes, HTML::tag("strong", $label) . ": " . $value);
}
echo HTML::tag("ul", implode("\n", $items));
