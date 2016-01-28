<?php
/**
 * Base class
 *
 * @author kent
 * @see Zesk_Content_Article
 */
class Class_Zesk_Content_Article extends Class_Object {
	public $id_column = "id";

	public $name_column = "title";

	public $options = array(
		'order_column' => 'order_index'
	);
}

