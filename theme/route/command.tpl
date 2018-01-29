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
if ($response->content_type() === "text/html") {
	echo HTML::tag("pre", implode("\n", $this->content));
} else {
	echo ArrayTools::join_wrap($this->content, "    ", "\n");
}
