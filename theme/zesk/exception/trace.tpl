<?php

$trace = $this->content;

echo html::tag_open('ol', '.exception-trace');
foreach ($trace as $trace_item) {
	echo $this->theme('exception/trace-item', array(
		"content" => $trace_item
	));
}
echo html::tag_close();
