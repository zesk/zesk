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
/* @var $content array */
/* @var $process string */
$nseconds = intval(microtime(true) - $content['time']);
$alive = to_bool(avalue($content, 'alive'));
$status = avalue($content, 'status');
$classes = [
	"daemon-module processes",
	$alive ? "ok" : "not-ok",
	$status,
];

$class = implode(" ", $classes);
if ($process === "me") {
	$process = $locale->__('Master Daemon Process');
} else {
	$process = preg_replace_callback('#\^([0-9]+)#', fn ($match) => " (#" . (intval($match[1]) + 1) . ")", $process);
}
//echo HTML::tag('li', HTML::tag('pre', _dump($content)));
echo HTML::tag_open("li", [
	"class" => $class,
]);

echo HTML::wrap($locale("[{process}] {status}", [
	"process" => $process,
	"status" => $status,
]), HTML::tag("strong", ".name", "[]"));

echo HTML::span(".status", $alive && $status === "up" ? "up" : "down");

echo HTML::span(".duration", $this->theme('duration', $nseconds));

echo HTML::tag_close("li");
