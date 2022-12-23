<?php declare(strict_types=1);
/* @var $this \zesk\Template */
/* @var $locale \zesk\Locale */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */
namespace zesk;

$results = $application->orm_module()->schema_synchronize();

echo HTML::tag('ol', HTML::tags('li', [], $results));

$this->response->content_type = 'text/plain';
