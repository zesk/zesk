<?php

/* You can override on a per-type basis in theme/content/group/type/view.tpl */
if (false) {
	/* @var $this Template */
	
	$zesk = $this->zesk;
	/* @var $zesk zesk\Kernel */
	
	$application = $this->application;
	/* @var $application TimeBank */
	
	$session = $this->session;
	/* @var $session Session */
	
	$router = $this->router;
	/* @var $request Router */
	
	$request = $this->request;
	/* @var $request Request */
	
	$response = $this->response;
	/* @var $response Response_HTML */
	
	$current_user = $this->current_user;
	/* @var $current_user User */
}

$group = $this->object;
/* @var $group Content_Group */

$menu = $this->menu;

$menu_remain = avalue($menu, "MenuRemain");

$group_object = $group->group_object();
if (is_string($menu_remain)) {
	$group_object->CodeName = $menu_remain;
	if (!$group_object->find()) {
		$response->error_404($group_object->words(__("{class_name-context-subject} not found")));
	}
	$html_class = strtolower($group_object->class_code_name());
	
	echo html::div(array(
		"class" => "back $html_class-back"
	), html::a($menu['URI'], __("Back to {0}", $group->Name)));
	
	echo html::div(array(
		"class" => $html_class
	), $group_object->output());
	return;
}

echo html::tag_open("div", ".article-group");
echo html::tag("h1", $group->Name);
if ($current_user && $current_user->can("edit", $group)) {
	echo html::tag_open("div", ".admin-edit");
	$sep = "&nbsp;&middot;&nbsp;";
	echo html::a($router->get_route('edit', $group), __("Edit {0}", $group->Name));
	echo $sep;
	echo html::a($router->get_route('list', $group), __("Manage Articles"));
	echo $sep;
	echo html::a($router->get_route('add', $group), __("Add {name-lower}", $group_object->class_code_name()));
	echo html::tag_close("div");
}
echo html::etag("p", array(
	"class" => "intro"
), $group->Body);
echo $group->theme(array(
	"view-item",
	"./content/group/view-item.tpl"
));
echo html::tag_close("div");
