<?php

$group = $this->object;
/* @var $group Content_Group */

$group_object = $group->group_object();

$query = $group_object->query()->where("Parent", $group)->limit(0, $group->DisplayCount);

$group->hook("query_alter", $query);

$template = zesk::get(get_class($group) . "::template", "view");

foreach ($query->object_iterator() as $object) {
	
	echo $object->output(array(
		$template
	));
}
