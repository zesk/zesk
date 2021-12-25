<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage theme
 * @author kent
 * @copyright &copy; 2018 Market Acumen, Inc.
 */
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
echo HTML::tag('h1', $locale('No results found for &ldquo;{q}&rdquo;', [
	'q' => htmlspecialchars($this->request->get('q')),
]));
echo $this->theme('search/form');
