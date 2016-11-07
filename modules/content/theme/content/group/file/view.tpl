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
/* @var $object \Content_Group_File */
echo HTML::div_open('.link-group');
echo HTML::tag('h1', $object->Name);
echo $this->theme('control/admin-edit');
echo etag("p", array(
	"class" => "intro"
), $object->Body);
echo $object->theme("file-list");
echo HTML::div_close();