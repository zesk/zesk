<?php
if (false) {
	/* @var $this Template */
	
	$zesk = $this->zesk;
	/* @var $zesk zesk\Kernel */
	
	$application = $this->application;
	/* @var $application TimeBank */
	
	$request = $this->request;
	/* @var $request Request */
	
	$response = $this->response;
	/* @var $response Response_HTML */
}

$request = $this->request;
/* @var $request Request */
$response = $this->response;
/* @var $response Response_HTML */
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
$zesk->hooks->call_arguments('response_html_start', $hook_parameters);
{
	$application->module->all_hook_array("headers", $hook_parameters);
	$zesk->hooks->call_arguments('headers', $hook_parameters);
	
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
	$application->module->all_hook_array("html", $hook_parameters);
	echo $response->doctype();
	$zesk->hooks->call_arguments("<html>", $hook_parameters);
	// Next line is @deprecated
	$zesk->hooks->call_arguments("response/html.tpl", $hook_parameters);
	echo html::tag_open('html', $response->html_attributes());
	{
		echo html::tag_open('head');
		{
			$application->module->all_hook_array('head', $hook_parameters);
			echo $zesk->hooks->call_arguments('<head>', $hook_parameters, '');
			echo $response->head_prefix();
			echo html::etag("title", $response->title());
			echo $response->metas();
			echo $response->links(array(
				'stylesheets_inline' => zesk::getb('page::stylesheets_inline', $this->stylesheets_inline)
			));
			echo $response->inline_styles();
			if ($ie6) {
				echo $response->scripts();
			}
			echo $zesk->hooks->call_arguments('</head>', $hook_parameters, '');
			echo $response->head_suffix();
		}
		echo html::tag_close('head') . "\n";
		
		echo $response->body_begin();
		{
			echo $zesk->hooks->call_arguments('<body>', $hook_parameters, '');
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
			echo $zesk->hooks->call_arguments('</body>', $hook_parameters, "");
		}
		log::debug("{elapsed} seconds", array(
			"elapsed" => sprintf("%.3f", microtime(true) - zesk::get('init'))
		));
		$application->module->all_hook_array('foot', $hook_parameters);
		echo "\n" . $response->body_end();
	}
	echo $zesk->hooks->call_arguments('</html>', $hook_parameters, "");
	echo "\n" . html::tag_close('html');
}
$zesk->hooks->call_arguments('response_html_end', $hook_parameters);


