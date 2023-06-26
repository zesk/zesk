<?php
declare(strict_types=1);

namespace zesk;

use zesk\Response\HTML as ResponseHTML;

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
	$application->invokeHooks(ResponseHTML::HOOK_HEAD_OPEN);
	echo $this->theme('Response/HTML/head/title');
	echo $this->theme('Response/HTML/head/metas');
	echo $this->theme('Response/HTML/head/links');
	echo $this->theme('Response/HTML/head/styles');
	$application->invokeHooks(ResponseHTML::HOOK_HEAD_CLOSE);
}
echo HTML::tag_close('head') . "\n";
