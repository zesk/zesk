<?php
/* @var $this \zesk\Template */
/* @var $zesk \zesk\Kernel */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response_Text_HTML */
namespace zesk;

$zesk->newline = "\n";

$results = $application->schema_synchronize();

echo HTML::tag('ul', HTML::tags('li', $results));

$this->response->content_type = "text/plain";
