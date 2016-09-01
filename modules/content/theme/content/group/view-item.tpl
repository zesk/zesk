<?php
if (false) {
	/* @var $this Template */
	
	$zesk = $this->zesk;
	/* @var $zesk zesk\Kernel */
	
	$application = $this->application;
	/* @var $application zesk\Application */
	
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
	
	$object = $this->object;
	/* @var $object Content_Group */
}

$group_object = $object->group_object();

$query = $group_object->query()->where("Parent", $object)->limit(0, $object->DisplayCount);

$object->hook("query_alter", $query);

$theme = $object->option("group_item_theme", "view");

foreach ($query->object_iterator() as $object) {
	echo $object->theme($theme);
}
