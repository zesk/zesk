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
?>
<h1>Something went wrong</h1>
<?php

echo HTML::tag('p', $this->message);

$href = $router->get_route('index', Controller_Forgot::class);
if ($href) {
	echo HTML::tag('p', HTML::tag('a', [
		'class' => 'forgot-index-link',
		'href' => $href,
	], $locale->__('Try again')));
}
