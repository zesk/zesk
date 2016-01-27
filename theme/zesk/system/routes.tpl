<?php

$router = $this->router;
$request = $this->request;

$show_weights = $request->getb('show_weights');

echo html::tag_open('ul');
foreach ($router->routes() as $route) {
	/* @var $route Router */
	$weight = $route->weight();
	echo html::tag('li', $route->original_pattern . "($weight)");
}
echo html::tag_close('ul');


