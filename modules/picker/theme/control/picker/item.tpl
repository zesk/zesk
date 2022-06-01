<?php declare(strict_types=1);
use zesk\HTML;

/* @var $object ORM */
$object = $this->object;
$id = $object->id();
echo HTML::div(HTML::addClass([
	'class' => 'item',
	'data-id' => $id,
], $this->selected ? 'selected' : ''), $object->theme([
	'picker-item',
	'view',
]) . HTML::hidden($this->column . '[]', $id, [
	'id' => null,
]));
