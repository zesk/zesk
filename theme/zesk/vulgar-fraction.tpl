<?php

$number = $this->content;
if ($number === 0) {
	echo "0";
	return;
}
if ($number < 0) {
	$sign = "-";
	$number = -$number;
} else {
	$sign = "";
}
$integer = intval($number);
$decimal = $number - $integer;

if (real_equal($decimal, 1, 0.01)) {
	// Handles 100.99
	$decimal = 0;
	$integer += 1;
}
echo $sign . ($integer === 0 ? "" : zesk::number_format($integer));
$map = array(
	"&#8531;" => 1.0 / 3.0,
	"&#8532;" => 2.0 / 3.0,

	"&#188;" => 0.25,
	"&#189;" => 0.5,
	"&#190;" => 0.75,

	"&#8528;" => 1 / 7,
	"&#8529;" => 1 / 9,
	"&#8530;" => 1 / 10,

	"&#8533;" => 1 / 5,
	"&#8534;" => 2 / 5,
	"&#8535;" => 3 / 5,
	"&#8536;" => 4 / 5,

	"&#8537;" => 1 / 6,
	"&#8538;" => 5 / 6,

	"&#8539;" => 1 / 8,
	"&#8540;" => 3 / 8,
	"&#8541;" => 5 / 8,
	"&#8542;" => 7 / 8
);
if (real_equal($decimal, 0)) {
	echo $integer === 0 ? "0" : "";
	return;
}
foreach ($map as $entity => $fraction) {
	if (real_equal($fraction, $decimal, 0.05)) {
		echo $entity;
		return;
	}
}
echo trim(number_format($decimal, 1), "0");
