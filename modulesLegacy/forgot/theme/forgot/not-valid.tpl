<?php declare(strict_types=1);
/**
 * @version $URL$
 * @author $Author: kent $
 * @package {package}
 * @subpackage {subpackage}
 * @copyright Copyright (C) 2016, {company}. All rights reserved.
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

/* @var $token string */
echo HTML::tag('h1', $locale->__('Something went wrong'));
echo HTML::etag('p', $this->message);
echo HTML::tag_open('div', '.detailed-help');
echo HTML::tag('p', HTML::wrap($locale->__('We received this: [{token}]; but were expecting something which looks more like this: [{example_token}].', [
	'token' => $token,
	'example_token' => md5(date('Y-m-d')),
]), HTML::tag('span', '.code', '[]'), HTML::tag('span', '.code', '[]')));
echo HTML::tag_close('div');
$href = $router->get_route('index', Controller_Forgot::class);
if ($href) {
	echo HTML::tag('p', HTML::tag('a', [
		'class' => 'forgot-index-link',
		'href' => $href,
	], $locale->__('Try again')));
}
