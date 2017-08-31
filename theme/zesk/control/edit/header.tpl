<?php
/**
 * 
 */
namespace zesk;

/* @var $this \zesk\Template */
/* @var $zesk \zesk\Kernel */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response_Text_HTML */
/* @var $current_user \zesk\User */
if ($this->parent === null) {
	$title = $this->title;
	if (!$title) {
		if (!$this->response) {
			throw new Exception_Semantics("Response should be set before calling template");
		}
		$title = $this->response->title();
	}
	if ($title) {
		echo HTML::tag('h1', '.title', $title);
	}
}
if (is_array($this->errors) && count($this->errors) > 0) {
	echo View_Errors::html($application, $this->errors);
}
