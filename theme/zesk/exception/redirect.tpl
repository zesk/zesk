<?php

/* @var $this \zesk\Template */
/* @var $application \zesk\Application */
/* @var $locale \zesk\Locale */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */

/* @var $exception \zesk\Exception_Redirect */
$response->redirect()->url($exception->url(), $exception->getMessage());
$status_code = $exception->status_code();
if ($status_code) {
	$response->status_code = intval($status_code);
}
$status_message = $exception->status_message();
if ($status_message) {
	$response->status_message = $status_message;
}