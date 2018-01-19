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
/* @var $response \zesk\Response_Text_HTML */
/* @var $current_user \User */
/* @var $object \Content_Video */
/* @var $class string */
$class = $this->class;

$class = CSS::add_class($class, "video");

/* @var $object Video */
echo HTML::div_open(array(
	"class" => $class
));
if ($current_user && $current_user->can($object, "edit")) {
	// TODO This is all wrong
	echo HTML::a("/manage/video/edit.php?ID=" . $object->id(), HTML::img($application, "/share/images/actions/edit.gif"), true);
}

echo HTML::div_close();
