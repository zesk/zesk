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
/* @var $object \Content_Group_File */
throw new Exception_Unimplemented();

backtrace();
$object = $this->object;
/* @var $object Content_Group_File */

$group_object = new Content_File();

echo $group_object->outputAllObjects("view", null, array(
	"Parent" => $object->id()
), $object->groupOrderBy(), 0, $object->member("DisplayCount", -1));
