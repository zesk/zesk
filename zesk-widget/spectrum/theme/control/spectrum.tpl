<?php declare(strict_types=1);
/**
 * @copyright &copy; 2022, Market Acumen, Inc.
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
/* @var $current_user \zesk\User */
$version = \Module_Spectrum::version;

$id = $this->id;
if (empty($id)) {
	$id = $this->name;
}

$value = $this->value;

if (!str_starts_with($value, '#')) {
	$value = "#$value";
}

$html_id = "jpicker-$id";

$options = [
	'preferredFormat' => 'hex6',
];

$response->jquery("\$('#$html_id').spectrum(" . JSON::encodeJavaScript($options) . ');');
$response->javascript('/share/spectrum/spectrum.js', [
	'share' => true,
]);
$response->css('/share/spectrum/spectrum.css', [
	'share' => true,
]);

$attributes = [
	'id' => $html_id,
];
echo HTML::input('hidden', $this->name, $value, $attributes);
