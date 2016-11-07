<?php

/* @var $object \Content_Link_Group */

$group_object = new Content_Link();

zesk()->obsolete();

// TODO Fix this
echo $group_object->outputAllObjects("view", null, array(
	"Parent" => $x->id()
), $x->groupOrderBy(), 0, $x->member("DisplayCount", -1));
