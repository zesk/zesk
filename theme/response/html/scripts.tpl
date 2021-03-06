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
/* @var $scripts array[] */
/* @var $jquery_ready string[] */
$result = array();
foreach ($response->html()->scripts() as $script_tag) {
	$name = $attributes = $content = $prefix = $suffix = null;
	extract($script_tag, EXTR_IF_EXISTS);
	if (empty($content)) {
		$content = "";
	}
	echo $prefix . HTML::tag($name, $attributes, $content) . $suffix . "\n";
}
$jquery_ready = $response->html()->jquery_ready();
if (count($jquery_ready)) {
	echo HTML::tag('script', array(
		'type' => 'text/javascript',
	), "\n\$(document).ready(function() {\n" . implode("\n", $jquery_ready) . "\n});\n") . "\n";
}
