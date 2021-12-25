<?php declare(strict_types=1);
namespace zesk;

/* @var $this Template */
/* @var $locale \zesk\Locale */
/* @var $application Application */
/* @var $route Route */
/* @var $request Request */
/* @var $response Response */

// Setup
$application->hooks->call("page.tpl", $this);

$wrap_html = $response->content_type === Response::CONTENT_TYPE_HTML;
$page_template = $response->option("theme", 'response/html');
if (isset($route) && $route instanceof Route) {
	$wrap_html = $response->option_bool('wrap_html', $route->option_bool('wrap_html', $wrap_html));
	$page_template = $route->option('page template', $page_template);
}
// Output
if ($wrap_html && $page_template) {
	echo $this->theme($page_template);
} else {
	echo $this->content;
}

$application->hooks->call("page.tpl-exit", $this);
