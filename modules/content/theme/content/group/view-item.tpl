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
/* @var $object \Content_Group */
$group_object = $object->group_object();

$query = $group_object->query_select()->where("Parent", $object)->limit(0, $object->DisplayCount);

$object->call_hook("query_alter", $query);

$theme = $object->option("group_item_theme", "view");

foreach ($query->object_iterator() as $object) {
	echo $object->theme($theme);
}
