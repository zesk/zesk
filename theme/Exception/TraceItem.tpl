<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk;

/* @var $this Template */

$trace_item = $this->getArray('content');
$file = $trace_item['file'] ?? '-no-file-';
$line = $trace_item['line'] ?? '-no-line-';
$function = $trace_item['function'];
$class = $trace_item['class'] ?? '-no-class-';
$type = $trace_item['type'] ?? '-no-type-';
$args = $trace_item['args'];

if (is_array($args)) {
	foreach ($args as $index => $arg) {
		$args[$index] = PHP::dump($arg);
	}
	$args = HTML::tags('li', '.arg', $args);
} else {
	$args = '';
}
echo HTML::tag_open('li');
echo HTML::tag(
	'div',
	'.method',
	HTML::etag('span', '.class', $class) .
	HTML::etag('span', '.type', $type) .
	HTML::etag('span', '.function', $function) .
	HTML::tag('ol', '.args', $args)
);
echo HTML::tag('div', '.location', HTML::etag('span', 'file', $file) . ':' . HTML::etag('span', '.line', $line));
echo HTML::tag_close('li');
