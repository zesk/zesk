<?php declare(strict_types=1);
namespace zesk;

/* @var $this \zesk\Template */
/* @var $application \zesk\Application */
/* @var $locale \zesk\Locale */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */

/* @var $exception \zesk\Exception_Redirect */
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