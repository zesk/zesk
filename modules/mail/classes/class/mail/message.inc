<?php

/**
 * Class for Mail_Message
 *
 * @author kent
 *
 */
class Class_Mail_Message extends zesk\Class_Object {
	public $find_keys = array(
		'message_id',
		'hash'
	);
	public $name = "Mail";
	public $has_many = array(
		'headers' => array(
			'class' => 'Mail_Header',
			'column' => 'mail'
		)
	);
	public $column_types = array(
		'id' => self::type_id,
		'hash' => self::type_hex,
		'message_id' => self::type_string,
		'mail_from' => self::type_string,
		'mail_to' => self::type_string,
		'subject' => self::type_string,
		'state' => self::type_integer,
		'date' => self::type_timestamp,
		'content_type' => self::type_string,
		'content' => self::type_string,
		'size' => self::type_integer,
		'user' => self::type_object
	);
	public $column_defaults = array(
		'state' => ''
	);
}
