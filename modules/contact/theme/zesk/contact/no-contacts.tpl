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
?>
<p class="error">You do not have any contacts yet. <?php

echo HTML::a($router->get_route('new', 'Contact'), 'Create a contact');
