<?php
/**
 *
 */
namespace zesk;

/* @var $this \zesk\Template */
/* @var $locale \zesk\Locale */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */
/* @var $current_user \zesk\User */
if ($this->parent === null) {
	$title = $this->title;
	if (!$title) {
		$title = $response->title();
	}
	if ($title) {
		echo HTML::tag('h1', '.title', $title);
	}
}
if (is_array($this->errors) && count($this->errors) > 0) {
	echo View_Errors::html($application, $this->errors);
}
