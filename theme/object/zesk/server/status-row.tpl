<?php
namespace zesk;

/* @var $this \zesk\Template */
/* @var $zesk \zesk\Kernel */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response_Text_HTML */
/* @var $current_user \zesk\User */
/* @var $object \zesk\Server */
$n_secs = Timestamp::now('UTC')->difference($object->alive, "second");

echo HTML::tag('div', '.col-xs-4 .server-name', $object->name_internal);
echo HTML::tag('div', '.col-xs-2 .server-load', $object->load);
echo HTML::tag('div', '.col-xs-3 .server-free-disk', Number::format_bytes(Number::parse_bytes($object->free_disk . " " . $object->free_disk_units)));
echo HTML::tag('div', '.col-xs-3 .server-alive', $n_secs);
