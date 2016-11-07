<?php

$router = $this->router;
$request = $this->request;

$show_weights = $request->getb('show_weights');

echo HTML::tag_open('ul');
foreach ($router->routes() as $route) {
	/* @var $route Router */
	$weight = $route->weight();
	echo HTML::tag('li', $route->original_pattern . "($weight)");
}
echo HTML::tag_close('ul');


