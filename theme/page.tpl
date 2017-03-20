<?php
namespace zesk;

/* @var $this Template */
/* @var $zesk zesk\Kernel */
/* @var $application Application */
/* @var $route Route */
/* @var $request Request */
/* @var $response Response_Text_HTML */

// Setup
$zesk->hooks->call("page.tpl", $this);

if (!$this->response) {
	$response = $this->response = $application->response();
}

$wrap_html = $response->content_type === "text/html";
$page_template = $zesk->configuration->path_get("zesk\Response_Text_HTML::theme", 'response/html');
if (isset($route) && $route instanceof Route) {
	$wrap_html = $response->option_bool('wrap_html', $route->option_bool('wrap_html', $wrap_html));
	$page_template = $route->option('page template', $page_template);
}
// Output
if ($wrap_html && $page_template) {
	// TODO Remove this (deprecated)
	$page_template = str::unsuffix($page_template, ".tpl");
	echo $this->theme($page_template, array(
		'content' => $this->content
	));
} else {
	echo $this->content;
}

$zesk->hooks->call("page.tpl-exit", $this);
