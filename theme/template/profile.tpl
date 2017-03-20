<?php
$times = $this->times;
$counts = $this->counts;

arsort($times, SORT_NUMERIC);
arsort($counts, SORT_NUMERIC);

echo HTML::div_open("#template-profile");
echo HTML::tag("h2", "zesk\Template profiling");
echo $this->theme('pairs', array(
	"content" => $times
));
echo $this->theme('pairs', array(
	"content" => $counts
));
echo HTML::div_close();
