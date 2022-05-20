<?php declare(strict_types=1);
/**
 *
 * For debugging, use
 *
 * <code>$application->theme("zesk/has_hook", ["content" => $application->hooks->has()]);</code>
 */
namespace zesk;

/* @var $this \zesk\Template */
/* @var $application \zesk\Application */
/* @var $locale \zesk\Locale */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */
$min = $max = null;
foreach ($this->content as $arr) {
	[$start, $stop] = $arr;
	$min = $min === null ? $start : min($min, $start);
	$max = min($max, $stop);
}
$range = $max - $min;
$rows = [];
foreach ($this->content as $key => $arr) {
	[$start, $stop, $count] = $arr;
	$rows[] = [
		$key,
		$arr[0],
		$this->theme('microsecond', $arr[1] - $min, $range),
		$this->theme('microsecond', $arr[2] - $min, $range),
	];
}
return $this->theme('table', [
	'headers' => $locale->__([
		'Hook',
		'First Time',
		'Last Time',
		'Number of times',
	]),
	'rows' => $rows,
]);
