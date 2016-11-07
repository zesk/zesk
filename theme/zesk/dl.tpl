<?php
$pairs = $this->content;
if (!$pairs) {
	$pairs = $this->variables;
}
if (!can_iterate($pairs)) {
	return;
}
$result = "";
$index = 0;
foreach ($pairs as $name => $value) {
	if ($value === null || $value === "") {
		continue;
	}
	$class = ($index % 2 === 0) ? ".even" : ".odd";
	$result .= HTML::tag('dt', $class, $name);
	$result .= HTML::tag('dd', $class, is_array($value) ? $this->theme('dl', array(
		'content' => $value
	)) : strval($value));
	$index = $index + 1;
}

echo HTML::etag('dl', $result);
