<?php declare(strict_types=1);
namespace zesk;

$times = $this->times;
$counts = $this->counts;

arsort($times, SORT_NUMERIC);
arsort($counts, SORT_NUMERIC);

echo HTML::div_open("#template-profile");
echo HTML::tag("h2", "zesk\Template profiling");
echo $this->theme('pairs', [
	"content" => $times,
]);
echo $this->theme('pairs', [
	"content" => $counts,
]);
echo HTML::div_close();
