<?php declare(strict_types=1);
namespace zesk;

/* @var $this \zesk\Template */
/* @var $application \zesk\Application */
/* @var $locale \zesk\Locale */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */

// For some bizarre reason, when outputting frames beyond 10, the output is immediately rendered to
// STDOUT and the outer page.tpl is NOT rendered; like
$trace = $this->content;
echo HTML::tag_open('ol', '.exception-trace');
$skip_frames = $request->getInt('skip_frames', 10);
$frames = $request->getInt('frames', 20);
foreach ($trace as $index => $trace_item) {
	echo $this->theme('exception/trace-item', [
		'content' => $trace_item,
	]);
	if ($frames > 0 && $index >= $frames - 1) {
		break;
	}
}
echo HTML::tag_close();
