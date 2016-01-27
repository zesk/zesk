<?php

/* @var $this Template */

$request = $this->request;
/* @var $request Request */
$response = $this->response;
/* @var $response Response_HTML */
if (!$request) {
	$request = $this->request = Request::instance();
}
if (!$response) {
	$response = $this->response = Response::instance();
}

gzip::start();
{
	$hook_parameters = array(
		$request,
		$response,
		$this
	);
	Module::all_hook_array("headers", $hook_parameters);
	zesk::hook_array('headers', $hook_parameters);

	$ie6 = false;
	if (zesk::getb("DOCTYPE::xhtml1-strict")) {
		$response->doctype("doctype", "xhtml1-strict");
	}
	if (zesk::getb("DOCTYPE::html-transitional")) {
		$response->doctype("doctype", "html-transitional");
	}
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
	Module::all_hook_array("html", $hook_parameters);
	echo $response->doctype();
	zesk::hook_array("<html>;response/html.tpl", $hook_parameters);
	echo html::tag_open('html', $response->html_attributes());
	{
		echo html::tag_open('head');
		{
			Module::all_hook_array('head', $hook_parameters);
			echo zesk::all_hook_array('Module::head;Object::head', $hook_parameters, '');
			echo zesk::hook_array('<head>', $hook_parameters, '');
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
			echo zesk::hook_array('</head>', $hook_parameters, '');
			echo $response->head_suffix();
		}
		echo html::tag_close('head') . "\n";

		echo $response->body_begin();
		{
			echo zesk::hook_array('<body>', $hook_parameters, '');
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
			echo zesk::hook_array('</body>', $hook_parameters, "");
		}
		log::debug("{elapsed} seconds", array(
			"elapsed" => sprintf("%.3f", microtime(true) - zesk::get('init'))
		));
		Module::all_hook_array('foot', $hook_parameters);
		zesk::all_hook_array('Object::page_foot', $hook_parameters);
		echo "\n" . $response->body_end();
	}
	echo zesk::hook_array('</html>', $hook_parameters, "");
	echo "\n" . html::tag_close('html');
}
gzip::end();

