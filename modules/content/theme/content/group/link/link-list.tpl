<?php

$x = $this->Object;
/* @var $x Content_Link_Group */

$group_object = new Content_Link();

echo $group_object->outputAllObjects("view", null, array(
	"Parent" => $x->id()
), $x->groupOrderBy(), 0, $x->member("DisplayCount", -1));
