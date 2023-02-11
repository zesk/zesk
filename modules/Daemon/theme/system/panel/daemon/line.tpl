<?php
declare(strict_types=1);
namespace zesk;

/* @var $this Template */
/* @var $application Application */
/* @var $locale Locale */
/* @var $router Router */
/* @var $route Route */
/* @var $request Request */
/* @var $response Response */
/* @var $content array */
/* @var $process string */
$nseconds = intval(microtime(true) - $content['time']);
$alive = toBool($content['alive'] ?? null);
$status = $content['status'] ?? null;
$classes = [
	'daemon-module processes',
	$alive ? 'ok' : 'not-ok',
	$status,
];

$class = implode(' ', $classes);
if ($process === 'me') {
	$process = $locale->__('Master Daemon Process');
} else {
	$process = preg_replace_callback('#\^([0-9]+)#', fn ($match) => ' (#' . (intval($match[1]) + 1) . ')', $process);
}
//echo HTML::tag('li', HTML::tag('pre', _dump($content)));
echo HTML::tag_open('li', [
	'class' => $class,
]);

echo HTML::wrap($locale('[{process}] {status}', [
	'process' => $process,
	'status' => $status,
]), HTML::tag('strong', '.name', '[]'));

echo HTML::span('.status', $alive && $status === 'up' ? 'up' : 'down');

echo HTML::span('.duration', $this->theme('duration', $nseconds));

echo HTML::tag_close('li');
