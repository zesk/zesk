<?php
/**
 * @copyright 2013 &copy; Market Acumen, Inc. 
 * @author kent
 *
 */
namespace zesk;

class Class_Mail_Content extends Class_Object {
	public $find_keys = array(
		'mail',
		'content_id',
		'content_data'
	);
	public $has_one = array(
		'mail' => 'zesk\\Mail_Message',
		'content_data' => 'zesk\\Content_Data'
	);
	public $column_defaults = array(
		'disposition' => '',
		'filename' => ''
	);
}
