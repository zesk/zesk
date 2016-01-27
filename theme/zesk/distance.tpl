<?php

$units = $this->get('units', zesk::get('distance::units', 'km'));
// By default, all units in kilometers


// Distance should always be passed in as "meters"
$distance = $this->content;
if (!is_numeric($distance)) {
	return;
}
if (abs($distance) < $this->get('epsilon', zesk::get('distance::epsilon', 0.2))) {
	echo html::tag('span', '.distance nearby', __('Nearby'));
	return;
}
switch ($units) {
	case "km":
		$units = "kilometer";
		$distance /= 1000;
		break;
	case "m":
		$units = "meter";
		break;
	case "mm":
		$units = "millimeter";
		$distance *= 1000000;
		break;
	case "cm":
		$units = "centimeter";
		$distance *= 100000;
		break;
	case "miles":
		$units = "mile";
		$distance *= 0.000621371;
		break;
	case "feet":
		$units = "feet";
		$distance *= 3.28084;
		break;
	case "inches":
		$units = "inch";
		$distance *= 39.3701;
		break;
}

echo html::span('.distance', $this->theme('vulgar-fraction', round($distance, 1)) . " " . __(lang::plural($units, $distance)));