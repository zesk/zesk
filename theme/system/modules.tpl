<?php declare(strict_types=1);
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
$items = [];
foreach ($application->modules->all_modules() as $module_data => $module) {
	/* @var $module \zesk\Module */
	$items[] = $module->name();
}

echo HTML::tag('ol', HTML::tags('li', [], $items));
