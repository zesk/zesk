<?php declare(strict_types=1);
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
/* @var $current_user \zesk\User */
/* @var $object \zesk\Server */
$n_secs = Timestamp::now('UTC')->difference($object->alive, 'second');

echo HTML::tag('div', '.col-xs-4 .server-name', $locale->__('Name'));
echo HTML::tag('div', '.col-xs-2 .server-load', $locale->__('Load'));
echo HTML::tag('div', '.col-xs-3 .server-free-disk', $locale->__('Free Disk'));
echo HTML::tag('div', '.col-xs-3 .server-alive', $locale->__('Last alive (secs)'));
