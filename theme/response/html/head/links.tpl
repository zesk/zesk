<?php declare(strict_types=1);
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
/* @var $links array[] */
foreach ($response->html()->links() as $tag) {
	$name = $attributes = $content = $prefix = $suffix = null;
	extract($tag, EXTR_IF_EXISTS);
	if (empty($content)) {
		$content = null;
	}
	echo $prefix . HTML::tag($name, $attributes, $content) . $suffix . "\n";
}
