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
/* @var $current_user \User */
/* @var $object \Content_Group */
$group_object = $object->group_object();

$query = $group_object->query_select()->addWhere('Parent', $object)->limit(0, $object->DisplayCount);

$object->callHook('query_alter', $query);

$theme = $object->option('group_item_theme', 'view');

foreach ($query->orm_iterator() as $object) {
	echo $object->theme($theme);
}
