<?php

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
use zesk\HTML;
use zesk\Timestamp;
use zesk\Number;

$n_secs = Timestamp::now('UTC')->difference($object->alive, "second");

echo HTML::tag('div', '.col-xs-6', $object->name_internal);
echo HTML::tag('div', '.col-xs-2 .server-load', $object->load);
echo HTML::tag('div', '.col-xs-2 .server-free-disk', Number::format_bytes($object->free_disk));
echo HTML::tag('div', '.col-xs-2 .server-alive', $n_secs);
