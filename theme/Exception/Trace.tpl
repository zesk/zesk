<?php
declare(strict_types=1);

namespace zesk;

use zesk\Locale\Locale;

/* @var $this Theme */
/* @var $application Application */
/* @var $locale Locale */
/* @var $router Router */
/* @var $request Request */
/* @var $response Response */

// For some bizarre reason, when outputting frames beyond 10, the output is immediately rendered to
// STDOUT and the outer page.tpl is NOT rendered; like
$trace = $this->content;
echo HTML::tagOpen('ol', '.exception-trace');
$skip_frames = $request->getInt('skip_frames', 10);
$frames = $request->getInt('frames', 20);
foreach ($trace as $index => $trace_item) {
	echo $this->theme('Exception/TraceItem', [
		'content' => $trace_item,
	]);
	if ($frames > 0 && $index >= $frames - 1) {
		break;
	}
}
echo HTML::tagClose();
