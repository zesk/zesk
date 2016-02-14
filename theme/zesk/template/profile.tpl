<?php
$times = $this->times;
$counts = $this->counts;

arsort($times, SORT_NUMERIC);
arsort($counts, SORT_NUMERIC);

echo html::div_open("#template-profile");
echo html::tag("h2", "Template profiling");
echo $this->theme('pairs', array(
	"content" => $times
));
echo $this->theme('pairs', array(
	"content" => $counts
));
echo html::div_close();