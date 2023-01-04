<?php declare(strict_types=1);
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
	public string $id_column = 'id';

	/**
	 *
	 * @var string
	 */
	public $contact_object_field = 'address';

	/**
	 *
	 * @var array
	 */
	public array $has_one = [
		'contact' => 'zesk\\Contact',
		'label' => 'zesk\\Contact_Label',
		'country' => 'zesk\\Country',
		'county' => 'zesk\\County',
	];

	/**
	 *
	 * @var array
	 */
	public array $column_types = [
		'id' => self::TYPE_ID,
		'contact' => self::TYPE_OBJECT,
		'label' => self::TYPE_OBJECT,
		'country' => self::TYPE_OBJECT,
		'unparsed' => self::TYPE_STRING,
		'name' => self::TYPE_STRING,
		'street' => self::TYPE_STRING,
		'additional' => self::TYPE_STRING,
		'city' => self::TYPE_STRING,
		'county' => self::TYPE_OBJECT,
		'province' => self::TYPE_STRING,
		'postal_code' => self::TYPE_STRING,
		'country_code' => self::TYPE_STRING,
		'latitude' => self::TYPE_DOUBLE,
		'longitude' => self::TYPE_DOUBLE,
		'geocoded' => self::TYPE_TIMESTAMP,
		'geocode_data' => self::TYPE_SERIALIZE,
		'created' => self::TYPE_CREATED,
		'modified' => self::TYPE_MODIFIED,
		'data' => self::TYPE_SERIALIZE,
	];

	/**
	 *
	 * @var array
	 */
	public array $column_defaults = [
		'name' => '',
	];
}
