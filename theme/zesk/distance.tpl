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

$map = array();
$map['raw_number'] = round($distance, 1);
$map['number'] = $this->theme('vulgar-fraction', round($distance, 1));
$map['unit'] = $units;
$map['units'] = __(zesk\Locale::plural($units, $distance));
$map['distance'] = $map['number'] . ' ' . $map['units'];

$format = $this->get("format", "{number} {units}");

echo html::span('.distance', map($format, $map));