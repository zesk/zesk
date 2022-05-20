<?php declare(strict_types=1);
namespace zesk;

/* @var $this \zesk\Template */
/* @var $application \zesk\Application */
/* @var $locale \zesk\Locale */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */
$duration = $this->content;
$use_unit = Timestamp::UNIT_SECOND;
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
	'units' => $locale->plural($locale->__($unit), $duration),
]);
