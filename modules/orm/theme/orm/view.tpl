<?php
$object = $this->object;
/* @var $object ORM */
if (($name = $object->class_object()->name_column) !== null) {
	echo $object->__get($name);
} else {
	echo $object->id();
}
