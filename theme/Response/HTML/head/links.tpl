<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage theme
 * @author kent
 * @copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk;

/* @var $this Template */
/* @var $application Application */
/* @var $locale Locale */
/* @var $session Session */
/* @var $router Router */
/* @var $route Route */
/* @var $request Request */
/* @var $response Response */
/* @var $links array[] */
foreach ($response->html()->links() as $tag) {
	$attributes = $tag['attributes'];
	$name = $tag['name'];
	$content = $tag['content'];
	$prefix = $tag['prefix'];
	$suffix = $tag['suffix'];
	if (empty($content)) {
		$content = null;
	}
	echo $prefix . HTML::tag($name, $attributes, $content) . $suffix . "\n";
}
