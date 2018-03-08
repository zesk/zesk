<?php
/**
 * 
 */
namespace zesk;

/**
 * @see Contact_Address
 * @author kent
 *
 */
class Class_Contact_Address extends Class_Contact_Info {
	/**
	 * 
	 * @var string
	 */
	public $id_column = "id";
	/**
	 * 
	 * @var string
	 */
	public $contact_object_field = "address";
	/**
	 * 
	 * @var array
	 */
	public $has_one = array(
		'contact' => "zesk\\Contact",
		'label' => 'zesk\\Contact_Label',
		'country' => 'zesk\\Country',
		'county' => 'zesk\\County'
	);
	
	/**
	 * 
	 * @var array
	 */
	public $column_types = array(
		"id" => self::type_id,
		"contact" => self::type_object,
		"label" => self::type_object,
		"country" => self::type_object,
		'unparsed' => self::type_string,
		'name' => self::type_string,
		'street' => self::type_string,
		'additional' => self::type_string,
		'city' => self::type_string,
		'county' => self::type_object,
		'province' => self::type_string,
		'postal_code' => self::type_string,
		'country_code' => self::type_string,
		'latitude' => self::type_double,
		'longitude' => self::type_double,
		'geocoded' => self::type_timestamp,
		'geocode_data' => self::type_serialize,
		'created' => self::type_created,
		'modified' => self::type_modified,
		'data' => self::type_serialize
	);
	
	/**
	 * 
	 * @var array
	 */
	public $column_defaults = array(
		'name' => ''
	);
}
