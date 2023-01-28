<?php declare(strict_types=1);
namespace zesk;

/* @var $this Template */
/* @var $application Application */
/* @var $locale Locale */
/* @var $router Router */
/* @var $route Route */
/* @var $request Request */
/* @var $response Response */
$configuration = $application->configuration;

$units = $this->get('units', $configuration->getPath('distance::units', 'km'));
// By default, all units in kilometers

// Distance should always be passed in as "meters"
$distance = $this->content;
if (!is_numeric($distance)) {
	return;
}
$epsilon = $this->epsilon ?? $configuration->getPath('distance::epsilon', 0.2);
if (abs($distance) < $epsilon) {
	echo HTML::tag('span', '.distance nearby', $locale('Nearby'));
	return;
}
switch ($units) {
	case 'km':
		$units = 'kilometer';
		$distance /= 1000;
		break;
	case 'm':
		$units = 'meter';
		break;
	case 'mm':
		$units = 'millimeter';
		$distance *= 1000000;
		break;
	case 'cm':
		$units = 'centimeter';
		$distance *= 100000;
		break;
	case 'miles':
		$units = 'mile';
		$distance *= 0.000621371;
		break;
	case 'feet':
		$units = 'feet';
		$distance *= 3.28084;
		break;
	case 'inches':
		$units = 'inch';
		$distance *= 39.3701;
		break;
}

$map = [];
$map['raw_number'] = round($distance, 1);
$map['number'] = $this->theme('vulgar-fraction', round($distance, 1));
$map['unit'] = $units;
$map['units'] = $locale($locale->plural($units, $distance));
$map['distance'] = $map['number'] . ' ' . $map['units'];

$format = $this->get('format', '{number} {units}');

echo HTML::span('.distance', map($format, $map));
