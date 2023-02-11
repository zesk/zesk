<?php
declare(strict_types=1);
use zesk\HTML;
use zesk\Timestamp;

/* @var $this \zesk\Template */
/* @var $locale \zesk\Locale */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */
/* @var $module_class string */
$service = $application->settings_registry();

$now = Timestamp::now('UTC');

foreach ([
	Timestamp::UNIT_MINUTE => $locale->__('Last minute run'),
	Timestamp::UNIT_HOUR => $locale->__('Last hour run'),
	Timestamp::UNIT_WEEK => $locale->__('Last week run'),
	Timestamp::UNIT_MONTH => $locale->__('Last month run'),
	Timestamp::UNIT_YEAR => $locale->__('Last year run'),
] as $unit => $label) {
	$suffix = "_$unit";
	$runtime = $service->get($module_class . '::last' . $suffix);
	$attributes = [];
	if (!$runtime) {
		$value = $locale->__('Never');
		$attributes['class'] = 'error';
	} else {
		$last_run = Timestamp::factory($runtime, 'UTC');
		$before_error = $now->duplicate()->addUnit(-1, $unit);
		if ($last_run->before($before_error)) {
			$attributes['class'] = 'error';
			$value = $last_run->format($locale, '{delta}');
		} else {
			$value = $last_run->format($locale, '{delta}');
		}
	}
	$items[] = HTML::tag('li', $attributes, HTML::tag('strong', $label) . ': ' . $value);
}
echo HTML::tag('ul', implode("\n", $items));
