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
/* @var $object Content_Group */
$menu = $this->menu;

$menu_remain = avalue($menu, "MenuRemain");

$group_object = $object->group_object();
if (is_string($menu_remain)) {
	$group_object->CodeName = $menu_remain;
	if (!$group_object->find()) {
		$response->error_404($group_object->words(__("{class_name-context-subject} not found")));
	}
	$html_class = strtolower($group_object->class_code_name());
	
	echo HTML::div(array(
		"class" => "back $html_class-back"
	), HTML::a($menu['URI'], __("Back to {0}", $object->Name)));
	
	echo HTML::div(array(
		"class" => $html_class
	), $group_object->theme());
	return;
}

echo HTML::tag_open("div", ".article-group");
echo HTML::tag("h1", $object->Name);
if ($current_user && $current_user->can("edit", $object)) {
	echo HTML::tag_open("div", ".admin-edit");
	$sep = "&nbsp;&middot;&nbsp;";
	echo HTML::a($router->get_route('edit', $object), __("Edit {0}", $object->Name));
	echo $sep;
	echo HTML::a($router->get_route('list', $object), __("Manage Articles"));
	echo $sep;
	echo HTML::a($router->get_route('add', $object), __("Add {name-lower}", $group_object->class_code_name()));
	echo HTML::tag_close("div");
}
echo HTML::etag("p", array(
	"class" => "intro"
), $object->Body);
echo $object->theme(array(
	"view-item",
	"./content/group/view-item.tpl"
));
echo HTML::tag_close("div");
