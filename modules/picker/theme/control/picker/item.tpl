<?php

/* @var $object Object */
$object = $this->object;
$id = $object->id();
echo html::div(html::add_class(array(
	"class" => "item",
	"data-id" => $id
), $this->selected ? "selected" : ""), $object->render(array(
	"picker-item",
	"view"
)) . html::hidden($this->column . '[]', $id, array("id" => null)));

