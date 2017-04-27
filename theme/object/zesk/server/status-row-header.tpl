<?php
/**
 * 
 */
namespace zesk;

/* @var $this \zesk\Template */
/* @var $zesk \zesk\Kernel */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response_Text_HTML */
/* @var $current_user \User */
/* @var $object \Server */
$n_secs = Timestamp::now('UTC')->difference($object->alive, "second");

echo HTML::tag('div', '.col-xs-4 .server-name', __("Name"));
echo HTML::tag('div', '.col-xs-2 .server-load', __("Load"));
echo HTML::tag('div', '.col-xs-3 .server-free-disk', __("Free Disk"));
echo HTML::tag('div', '.col-xs-3 .server-alive', __("Last alive (secs)"));
