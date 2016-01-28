<?php
throw new Exception_Unimplemented();

backtrace();
$object = $this->object;
/* @var $object Content_Group_File */

$group_object = new Content_File();

echo $group_object->outputAllObjects("view", null, array(
	"Parent" => $object->id()
), $object->groupOrderBy(), 0, $object->member("DisplayCount", -1));
