<?php declare(strict_types=1);
namespace zesk;

/* @var $this Template */
/* @var $application Application */
/* @var $locale Locale */

$duration = intval($this->get('content'));
$use_unit = Temporal::UNIT_SECOND;
$prefix = '';
foreach (Timestamp::$UNITS_TRANSLATION_TABLE as $unit => $seconds) {
	if ($duration > $seconds * 2) {
		$use_unit = $unit;
		$duration = floor($duration / $seconds);
		$prefix = '~';
		break;
	}
}
echo $locale->__('{prefix}{n} {units}', [
	'prefix' => $prefix,
	'n' => $duration,
	'units' => $locale->plural($locale->__($use_unit), $duration),
]);
