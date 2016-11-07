<?php

/**
 * @see Contact_Address
 * @author kent
 *
 */
class Class_Contact_Address extends Class_Contact_Info {

	public $contact_object_field = "address";

	public $has_one = array(
		'contact' => "Contact",
		'label' => 'Contact_Label',
		'country' => 'Country',
		'county' => 'County'
	);
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

	public $column_defaults = array(
		'name' => ''
	);
}
