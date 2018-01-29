<?php
/**
 * @copyright &copy; 2016 Market Acumen, Inc.
 */
namespace zesk;

/* @var $this Template */
/* @var $locale \zesk\Locale */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $request \zesk\Router */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */
$trace_item = $this->content;
if (!is_array($trace_item)) {
	return;
}
$file = $line = $item = $function = $class = $type = $args = null;
extract($trace_item);
if (is_array($args)) {
	foreach ($args as $index => $arg) {
		$args[$index] = PHP::dump($arg);
	}
	$args = HTML::tags("li", '.arg', $args);
} else {
	$args = "";
}
echo HTML::tag_open('li');
echo HTML::tag('div', '.method', HTML::etag('span', '.class', $class) . HTML::etag('span', '.type', $type) . HTML::etag('span', '.function', $function) . HTML::tag('ol', '.args', $args));
echo HTML::tag('div', '.location', HTML::etag('span', 'file', $file) . ' ' . HTML::etag('span', '.line', $line));
echo HTML::tag_close();
