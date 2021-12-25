<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage theme
 * @author kent
 * @copyright &copy; 2018 Market Acumen, Inc.
 */
namespace zesk;

/* @var $this \zesk\Template */
/* @var $locale \zesk\Locale */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */
/* @var $current_user \zesk\User */
$decimals = $this->get1("1;decimals");
if (!$decimals) {
	$decimals = $application->configuration->path_get_first([
		[
			Locale::class,
			"percent_decimals",
		],
		[
			Locale::class,
			"numeric_decimals",
		],
	], 0);
}
echo sprintf("%.${decimals}f", $this->get1("0;content")) . "%";
