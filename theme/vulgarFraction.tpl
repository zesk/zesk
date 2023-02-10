<?php declare(strict_types=1);
/**
 * Output a number plus a fractional symbol which represents the closest value to the fractional portion.
 *
 * Supports fractions up through tenths
 */
namespace zesk;

/* @var $this Template */
/* @var $application Application */
/* @var $locale Locale */
$number = $this->content;
if ($number === 0) {
	echo '0';
	return;
}
if ($number < 0) {
	$sign = '-';
	$number = -$number;
} else {
	$sign = '';
}
$integer = intval($number);
$decimal = $number - $integer;

$integer_html = $sign . ($integer === 0 ? '' : $locale->number_format($integer));
$map = [
	// 2 denominator
	'&#189;' => 0.5,

	// 3 denominator
	'&#8531;' => 1.0 / 3.0,
	'&#8532;' => 2.0 / 3.0,

	// 4 denominator
	'&#188;' => 0.25,
	'&#190;' => 0.75,

	// 5 denominator
	'&#8533;' => 1 / 5,
	'&#8534;' => 2 / 5,
	'&#8535;' => 3 / 5,
	'&#8536;' => 4 / 5,

	// 6 denominator
	'&#8537;' => 1 / 6,
	'&#8538;' => 5 / 6,

	// 7 denominator
	'&#8528;' => 1 / 7,
	'<sup>2</sup>&frasl;<sub>7</sub>' => 2 / 7,
	'<sup>3</sup>&frasl;<sub>7</sub>' => 3 / 7,
	'<sup>4</sup>&frasl;<sub>7</sub>' => 4 / 7,
	'<sup>5</sup>&frasl;<sub>7</sub>' => 5 / 7,
	'<sup>6</sup>&frasl;<sub>7</sub>' => 6 / 7,

	// 8 denominator
	'&#8539;' => 1 / 8,
	'&#8540;' => 3 / 8,
	'&#8541;' => 5 / 8,
	'&#8542;' => 7 / 8,

	// 9 denominator
	'&#8529;' => 1 / 9,
	'<sup>2</sup>&frasl;<sub>9</sub>' => 2 / 9,
	'<sup>4</sup>&frasl;<sub>9</sub>' => 4 / 9,
	'<sup>5</sup>&frasl;<sub>9</sub>' => 5 / 9,
	'<sup>7</sup>&frasl;<sub>9</sub>' => 7 / 9,
	'<sup>8</sup>&frasl;<sub>9</sub>' => 8 / 9,

	// 10 denominator
	'&#8530;' => 1 / 10,
	'<sup>3</sup>&frasl;<sub>10</sub>' => 0.3,
	'<sup>7</sup>&frasl;<sub>10</sub>' => 0.7,
	'<sup>9</sup>&frasl;<sub>10</sub>' => 0.9,
];
if (real_equal($decimal, 0)) {
	echo($integer === 0 ? '0' : $integer_html);
	return;
}

$decimals = $this->getInt('decimals', 1);
$epsilon = $this->get('epsilon');
if (!$epsilon) {
	$epsilon = 0.1** $decimals / 2.0;
}
$closest = null;
$closest_delta = 0;
foreach ($map as $entity => $fraction) {
	if (real_equal($fraction, $decimal, $epsilon)) {
		$delta = abs($fraction - $decimal);
		if ($closest === null || $delta < $closest_delta) {
			$closest = $entity;
			$closest_delta = $delta;
		}
	}
}
if ($closest) {
	echo $integer_html . $closest;
	return;
}

$result = trim(number_format($decimal, $decimals), '0');
if ($result === '.') {
	echo $integer_html;
} elseif ($result === '1.') {
	echo $sign . $locale->number_format($integer + 1);
} else {
	echo $integer_html . ($integer === 0 ? '0' : '') . $result;
}
