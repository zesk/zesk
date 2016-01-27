<?php

$trace_item = $this->content;
if (!is_array($trace_item)) {
	return;
}
$file = $line = $item = $function = $class = $type = $args = null;
extract($trace_item);
if (is_array($args)) {
	foreach ($args as $index => $arg) {
		$args[$index] = php::dump($arg);
	}
	$args = html::tags("li", '.arg', $args);
} else {
	$args = "";
}
echo html::tag_open('li');
echo html::tag('div', '.method', html::etag('span', '.class', $class) . html::etag('span', '.type', $type) . html::etag('span', '.function', $function) . html::tag('ol', '.args', $args));
echo html::tag('div', '.location', html::etag('span', 'file', $file) . html::etag('span', '.line', $line));
echo html::tag_close();
