<?php
declare(strict_types=1);
namespace zesk;

/* @var $this Theme */
/* @var $application Kernel */
/* @var $application Application */
/* @var $request Request */
/* @var $response Response */

/* @var $hook_parameters array */
/* @var $head_prefix string */
/* @var $head_suffix string */
/* @var $title string */
echo HTML::tag_open('head');
{
	echo $application->modules->allHookArguments('head', $hook_parameters, '');
	echo $application->hooks->callArguments('<head>', $hook_parameters, '');
	echo $this->theme('Response/HTML/head/title');
	echo $this->theme('Response/HTML/head/metas');
	echo $this->theme('Response/HTML/head/links');
	echo $this->theme('Response/HTML/head/styles');
	echo $application->hooks->callArguments('</head>', $hook_parameters, '');
	echo $application->modules->allHookArguments('head_close', $hook_parameters, '');
}
echo HTML::tag_close('head') . "\n";
