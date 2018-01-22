<?php
/**
 *
 */
namespace zesk;

/* @var $this Template */
/* @var $application Kernel */
/* @var $application Application */
/* @var $request Request */
/* @var $response Response_Text_HTML */
$request = $this->request;
$response = $this->response;
if (!$request) {
	$request = $this->request = $application->request();
}
if (!$response) {
	$response = $this->response = $application->response();
}

$hook_parameters = array(
	$request,
	$response,
	$this
);
$application->hooks->call_arguments('response_html_start', $hook_parameters);
{
	$application->modules->all_hook_arguments("headers", $hook_parameters);
	$application->hooks->call_arguments('headers', $hook_parameters);

	$ie6 = false;
	if ($request->user_agent_is('ie6')) {
		$response->doctype("xhtml-transitional");
		$ie6 = true;
	}
	if ($request->user_agent_is('ie7')) {
		$response->doctype("doctype", "xhtml-transitional");
	}
	if ($this->has("body_attributes")) {
		$response->body_attributes($this->body_attributes);
	}
	if ($this->has('doctype')) {
		$response->doctype($this->doctype);
	}
	$application->modules->all_hook_arguments("html", $hook_parameters);
	echo $response->doctype();
	$application->hooks->call_arguments("<html>", $hook_parameters);
	// Next line is @deprecated
	$application->hooks->call_arguments("response/html.tpl", $hook_parameters);
	echo HTML::tag_open('html', $response->html_attributes());
	{
		echo HTML::tag_open('head');
		{
			$application->modules->all_hook_arguments('head', $hook_parameters);
			echo $application->hooks->call_arguments('<head>', $hook_parameters, '');
			echo $response->head_prefix();
			echo HTML::etag("title", $response->title());
			echo $response->metas();
			echo $response->links(array(
				'stylesheets_inline' => $this->stylesheets_inline
			));
			echo $response->inline_styles();
			if ($ie6) {
				echo $response->scripts();
			}
			echo $application->hooks->call_arguments('</head>', $hook_parameters, '');
			echo $response->head_suffix();
		}
		echo HTML::tag_close('head') . "\n";

		echo $response->body_begin();
		{
			echo $application->hooks->call_arguments('<body>', $hook_parameters, '');
			foreach (array(
				'content',
				'page_contents',
				'page_content'
			) as $k) {
				if ($this->has($k)) {
					echo $this->get($k);
					break;
				}
			}
			if (!$ie6) {
				echo $response->scripts();
			}
			echo $application->hooks->call_arguments('</body>', $hook_parameters, "");
		}
		$application->modules->all_hook_arguments('foot', $hook_parameters);
		echo "\n" . $response->body_end();
	}
	echo $application->hooks->call_arguments('</html>', $hook_parameters, "");
	echo "\n" . HTML::tag_close('html');
}
$application->hooks->call_arguments('response_html_end', $hook_parameters);


