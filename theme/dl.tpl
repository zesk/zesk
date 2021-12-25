<?php declare(strict_types=1);
namespace zesk;

/* @var $this \zesk\Template */
/* @var $locale \zesk\Locale */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */
$pairs = $this->content;
if (!$pairs) {
	$pairs = $this->variables;
}
if (!can_iterate($pairs)) {
	return;
}
$result = "";
$index = 0;
foreach ($pairs as $name => $value) {
	if ($value === null || $value === "") {
		continue;
	}
	$class = ($index % 2 === 0) ? ".even" : ".odd";
	$result .= HTML::tag('dt', $class, $name);
	$result .= HTML::tag('dd', $class, is_array($value) ? $this->theme('dl', [
		'content' => $value,
	]) : strval($value instanceof \Closure ? $locale->hooks->callable_string($value) : $value));
	$index = $index + 1;
}

echo HTML::etag('dl', $result);
