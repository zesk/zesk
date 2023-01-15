<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage theme
 * @author kent
 * @copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk;

/* @var $this Template */
/* @var $application Application */
$decimals = $this->getFirst('1;decimals');
if ($decimals === null) {
	$decimals = $application->configuration->getFirstPath([
		[
			Locale::class,
			'percent_decimals',
		],
		[
			Locale::class,
			'numeric_decimals',
		],
	], 0);
}
echo sprintf("%.${decimals}f", $this->getFirst('0;content')) . '%';
