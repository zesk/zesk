<?php

/**
 * Class_Content_Link
 */
class Class_Content_Link extends zesk\Class_Object {

	public $find_keys = array(
		"Hash", 
		"Parent"
	);

	public $column_types = array(
		'Hash' => 'hex', 
		'FirstClick' => 'timestamp', 
		'LastClick' => 'timestamp', 
		'Created' => 'timestamp', 
		'Modified' => 'timestamp'
	);

	public $has_one = array(
		'Parent' => 'Content_Link'
	);
}
