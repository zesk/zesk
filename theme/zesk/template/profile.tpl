<?php

$times = $this->times;
$counts = $this->counts;

arsort($times, SORT_NUMERIC);
arsort($counts, SORT_NUMERIC);

echo $this->theme('pairs', $times);
echo $this->theme('pairs', $counts);
