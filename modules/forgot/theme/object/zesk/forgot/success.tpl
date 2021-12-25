<?php declare(strict_types=1);
namespace zesk;

/* @var $this \zesk\Template */
/* @var $application \zesk\Application */
/* @var $locale \zesk\Locale */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */
/* @var $current_user \zesk\User */
/* @var $object \zesk\Forgot */
echo HTML::tag('h1', $locale->__('Your password has been updated'));
echo HTML::tag('p', HTML::wrap($locale->__('Please [login] to access your account.'), HTML::a($router->get_route("login", Controller_Login::class), '[]')));
