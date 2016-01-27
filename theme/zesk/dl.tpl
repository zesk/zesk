<?php
$pairs = $this->content;
if (!$pairs) {
	$pairs = $this->variables;
}
if (!can_iterate($pairs)) {
	return;
}
$result = "";
foreach ($pairs as $name => $value) {
	if ($value === null || $value === "") {
		continue;
	}
	$result .= html::tag('dt', $name);
	$result .= html::tag('dd', is_array($value) ? $this->theme('dl', array(
		'content' => $value
	)) : strval($value));
}

echo html::etag('dl', $result);
