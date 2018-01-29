<?php
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
/* @var $current_user \User */
/* @var $object \Content_File */
echo HTML::tag_open('div', array(
	"class" => CSS::add_class("file", $this->class)
));
// TODO Fix this
$uri = URL::query_append("/download.php", array(
	"FileGroup" => $object->Parent,
	"ID" => $object->ID
));
echo HTML::a_condition($uri === $request->path(), $uri, array(
	"class" => "title"
), $object->Name);
echo $this->theme('control/admin-edit');
echo HTML::etag("p", array(
	"class" => "desc"
), $object->Body);
echo HTML::tag_close('div');
