<?php
if (false) {
	/* @var $this Template */
	
	$application = $this->application;
	/* @var $application Application */
	
	$request = $this->request;
	/* @var $request Request */
	
	$response = $this->response;
	/* @var $response Response_HTML */

	/* @var $route Route */
	$route = $this->route;
}

// Setup
zesk::hook("page.tpl", $this);

if (!$this->response) {
	$response = $this->response = $application->response();
}

$wrap_html = $response->content_type === "text/html";
$page_template = zesk::get('page template', 'response/html');
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

zesk::hook("page.tpl-exit", $this);
