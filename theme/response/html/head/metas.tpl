<?php
/**
 * @package zesk
 * @subpackage theme
 * @author kent
 * @copyright &copy; 2018 Market Acumen, Inc.
 */
namespace zesk;

/* @var $this \zesk\Template */
/* @var $application \zesk\Application */
/* @var $locale \zesk\Locale */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */
/* @var $metas array */
foreach ($response->html()->metas() as $attrs) {
	echo HTML::tag('meta', $attrs) . "\n";
}
