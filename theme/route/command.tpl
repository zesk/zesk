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

if ($response->content_type() === "text/html") {
	echo HTML::tag("pre", implode("\n", $this->content));	
} else {
	echo arr::join_wrap($this->content, "    ", "\n");
}
