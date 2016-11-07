<?php
$min = $max = null;
foreach ($this->content as $arr) {
	list($start, $stop) = $arr;
	$min = $min === null ? $start : min($min, $start);
	$max = min($max, $stop);
}
$range = $max - $min;
$rows = array();
foreach ($this->content as $key => $arr) {
	list($start, $stop, $count) = $arr;
	$rows[] = array(
		$key,
		$arr[0],
		$this->theme("microsecond", $arr[1] - $min, $range),
		$this->theme("microsecond", $arr[2] - $min, $range)
	);
}
return $this->theme('table', array(
	'headers' => array(
		"Hook",
		"First Time",
		"Last Time",
		"Number of times"
	),
	'rows' => $rows
));
