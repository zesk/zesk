<?php

use zesk\HTML;

/* @var $object Object */
$object = $this->object;
$id = $object->id();
echo HTML::div(HTML::add_class(array(
	"class" => "item",
	"data-id" => $id
), $this->selected ? "selected" : ""), $object->theme(array(
	"picker-item",
	"view"
)) . HTML::hidden($this->column . '[]', $id, array("id" => null)));

