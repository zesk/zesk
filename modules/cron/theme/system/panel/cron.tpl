<?php
use zesk\HTML;
use zesk\Timestamp;

/* @var $this \zesk\Template */
/* @var $locale \zesk\Locale */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response_Text_HTML */
/* @var $current_user \User */
/* @var $module_class string */
$settings = $application->settings_registry();

$now = Timestamp::now("UTC");

foreach (array(
	Timestamp::UNIT_MINUTE => $locale->__("Last minute run"),
	Timestamp::UNIT_HOUR => $locale->__("Last hour run"),
	Timestamp::UNIT_WEEK => $locale->__("Last week run"),
	Timestamp::UNIT_MONTH => $locale->__("Last month run"),
	Timestamp::UNIT_YEAR => $locale->__("Last year run")
) as $unit => $label) {
	$suffix = "_$unit";
	$runtime = $settings->get($module_class . '::last' . $suffix);
	$attributes = array();
	if (!$runtime) {
		$value = $locale->__("Never");
		$attributes['class'] = 'error';
	} else {
		$last_run = Timestamp::factory($runtime, "UTC");
		$before_error = $now->duplicate()->add_unit(-1, $unit);
		if ($last_run->before($before_error)) {
			$attributes['class'] = 'error';
			$value = $last_run->format($locale, "{delta}");
		} else {
			$value = $last_run->format($locale, "{delta}");
		}
	}
	$items[] = HTML::tag('li', $attributes, HTML::tag("strong", $label) . ": " . $value);
}
echo HTML::tag("ul", implode("\n", $items));
