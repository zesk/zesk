<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk;

use zesk\Response\HTML as ResponseHTML;

/* @var $this Theme */
/* @var $application Kernel */
/* @var $application Application */
/* @var $request Request */
/* @var $response Response */
/* @var $body_attributes array */
/* @var $html_attributes array */
/* @var $html_attributes array */

$hook_parameters = [
	$request, $response, $this,
];
$application->invokeHooks(ResponseHTML::HOOK_HEADERS, $hook_parameters);
{
	echo $this->theme('Response/HTML/head/doctype');
	echo HTML::tag_open('html', $response->htmlAttributes());
	$application->invokeHooks(ResponseHTML::HOOK_HTML_OPEN, $hook_parameters);
	{
		echo $this->theme('Response/HTML/head', [
			'hook_parameters' => $hook_parameters,
		]);
		echo HTML::tag_open('body', $response->bodyAttributes());
		{
			$application->invokeHooks(ResponseHTML::HOOK_BODY_OPEN, $hook_parameters);
			foreach ([
						 'content', 'page_contents', 'page_content',
					 ] as $k) {
				if ($this->has($k)) {
					echo $this->get($k);
					break;
				}
			}
			$application->invokeHooks(ResponseHTML::HOOK_FOOT, $hook_parameters);
			echo $this->theme('Response/HTML/scripts');
			$application->invokeHooks(ResponseHTML::HOOK_BODY_CLOSE, $hook_parameters);
		}

		echo "\n" . HTML::tag_close('body');
	}
	$application->invokeHooks(ResponseHTML::HOOK_HTML_CLOSE, $hook_parameters);
	echo "\n" . HTML::tag_close('html');
}
$application->invokeHooks(ResponseHTML::HOOK_DONE, $hook_parameters);
