<?php
namespace zesk;

/* @var $widget Widget */
/* @var $row_widget Control_Row */
/* @var $this_row Control_Row */
$odd = 0;
$total = 0;
/* @var $query Database_Query_Select */
$query = $this->query;
foreach ($query->object_iterator() as $key => $object) {
	
	$this_row = clone $row_widget;
	
	$this_row->set_theme_variables(array(
		"odd" => $odd,
		"key" => $key,
		"object" => $object,
		"row_index" => $total
	));
	
	$widget->children_hook("control_list_row", $object, $this_row, $this);
	
	echo $this_row->execute($object, true);
	$odd = 1 - $odd;
	++$total;
}
if ($total === 0) {
	echo $this->theme($this->theme_empty, array(), array(
		'first' => true
	));
}
