<?php declare(strict_types=1);
namespace zesk;

/* @var $this Template */
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
	echo $application->modules->all_hook_arguments('head', $hook_parameters, '');
	echo $application->hooks->callArguments('<head>', $hook_parameters, '');
	echo $this->theme('response/html/head/title');
	echo $this->theme('response/html/head/metas');
	echo $this->theme('response/html/head/links');
	echo $this->theme('response/html/head/styles');
	echo $application->hooks->callArguments('</head>', $hook_parameters, '');
	echo $application->modules->all_hook_arguments('head_close', $hook_parameters, '');
}
echo HTML::tag_close('head') . "\n";
