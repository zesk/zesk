<?php
namespace zesk;

/* @var $this Template */
/* @var $application Kernel */
/* @var $application Application */
/* @var $request Request */
/* @var $response Response_Text_HTML */

/* @var $hook_parameters array */
/* @var $head_prefix string */
/* @var $head_suffix string */
/* @var $title string */
echo HTML::tag_open('head');
{
	echo $application->modules->all_hook_arguments('head', $hook_parameters, '');
	echo $application->hooks->call_arguments('<head>', $hook_parameters, '');
	echo HTML::etag("title", $response->title());
	echo $this->theme("response/html/head/metas");
	echo $this->theme("response/html/head/links");
	echo $response->links(array(
		'stylesheets_inline' => $this->stylesheets_inline
	));
	echo $this->theme("response/html/head/styles");
	echo $application->hooks->call_arguments('</head>', $hook_parameters, '');
	echo $application->modules->all_hook_arguments('head_close', $hook_parameters, '');
}
echo HTML::tag_close('head') . "\n";
