<?php
declare(strict_types=1);
namespace zesk;

use zesk\Locale\Locale;

/* @var $this Theme */
/* @var $application Application */
/* @var $locale Locale */
/* @var $session Session */
/* @var $router Router */
/* @var $route Route */
/* @var $request Request */
/* @var $response Response */

/* @var $exception Redirect */
/* @var $url string */
if ($response->optionBool('debug_redirect')) {
	$original_url = $exception->url();
	$url = $response->redirect()->processURL($original_url);
	echo $this->theme('zesk/exception/redirect-debug', [
		'content' => HTML::a($url, $url),
		'url' => $url,
		'original_url' => $original_url,
	]);
} else {
	$url = $response->redirect()->handleException($exception);
	echo HTML::a($url, $url);
}
