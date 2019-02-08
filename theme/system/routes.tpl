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
/* @var $current_user \User */
$show_weights = $request->getb('show_weights');

echo HTML::tag_open('ul');
foreach ($router->routes() as $route) {
	/* @var $route Router */
	$weight = $route->weight();
	echo HTML::tag('li', $route->original_pattern . "($weight)");
}
echo HTML::tag_close('ul');
