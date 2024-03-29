<?php
declare(strict_types=1);

namespace zesk;

/* @var $this Theme */
/* @var $locale Locale\Locale */
/* @var $application Application */
/* @var $route Route */
/* @var $request Request */

/* @var $response Response */

use zesk\Response\HTML as ResponseHTML;

// Setup
$application->invokeHooks(ResponseHTML::HOOK_PAGE_OPEN, [$request, $response, $this]);

$wrap_html = $response->contentType() === Response::CONTENT_TYPE_HTML;
$page_template = $response->option('theme', 'Response/HTML');
if (isset($route) && $route instanceof Route) {
	$wrap_html = $response->optionBool('wrap_html', $route->optionBool('wrap_html', $wrap_html));
	$page_template = $route->option('page template', $page_template);
}
// Output
if ($wrap_html && $page_template) {
	echo $this->theme($page_template, ['content' => $this->getString('content')]);
} else {
	echo $this->getString('content');
}

$application->invokeHooks(ResponseHTML::HOOK_PAGE_CLOSE, [$request, $response, $this]);
