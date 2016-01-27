<?php
/* @var $widget Widget */
$widget = $this->widget;
$odd = 0;
$total = 0;
/* @var $query Database_Query_Select */
$query = $this->query;
foreach ($query->object_iterator() as $key => $object) {
	$widget->children_hook("control_list_row", $object, $this);
	$row = $this->theme($this->theme_row, array(
		'key' => $key,
		'object' => $object,
		'odd' => $odd
	));
	echo $this->row_tag ? html::tag($this->row_tag, map($this->row_attributes, $object->variables() + array(
		"odd" => $odd
	)), $row) : $row;
	$odd = 1 - $odd;
	++$total;
}
if ($total === 0) {
	echo $this->theme($this->theme_empty, array(), array(
		'first' => true
	));
}
