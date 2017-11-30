<?php
/**
 * 
 */
namespace zesk;

/**
 * Class for Mail_Message
 * @see Mail_Message
 * @author kent
 *
 */
class Class_Mail_Message extends Class_Object {
	/**
	 * 
	 * @var string
	 */
	public $id_column = "id";
	
	/**
	 * 
	 * @var array
	 */
	public $find_keys = array(
		'message_id',
		'hash'
	);
	/**
	 * 
	 * @var string
	 */
	public $name = "Mail";
	
	/**
	 * 
	 * @var array
	 */
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
	
	/**
	 *
	 * @var array
	 */
	public $has_one = array(
		'user' => 'zesk\\User'
	);
	
	/**
	 *
	 * @var array
	 */
	public $has_many = array(
		'headers' => array(
			'class' => 'zesk\\Mail_Header',
			'column' => 'mail'
		)
	);
	
	/**
	 * 
	 * @var array
	 */
	public $column_defaults = array(
		'state' => 0
	);
}
