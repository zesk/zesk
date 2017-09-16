<?php
namespace zesk;

$trace = $this->content;

echo HTML::tag_open('ol', '.exception-trace');
foreach ($trace as $trace_item) {
	echo $this->theme('exception/trace-item', array(
		"content" => $trace_item
	));
}
echo HTML::tag_close();
