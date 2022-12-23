<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 * @see Content_Image
 * @author kent
 */
class Class_Content_Image extends Class_Base {
	/**
	 *
	 * @var string
	 */
	public string $id_column = 'id';

	/**
	 *
	 * @var string
	 */
	public string $name = 'Image';

	/**
	 *
	 * @var string
	 */
	public $name_column = 'title';

	/**
	 *
	 * @var array
	 */
	public array $find_keys = [
		'data',
		'path',
	];

	/**
	 *
	 * @var array
	 */
	public array $has_one = [
		'data' => Content_Data::class,
	];

	/**
	 *
	 * @var array
	 */
	public array $has_many = [
		'users' => [
			'class' => User::class,
			'link_class' => User_Content_Image::class,
			'foreign_key' => 'image',
			'far_key' => 'user',
		],
	];

	/**
	 *
	 * @var array
	 */
	public array $column_types = [
		'id' => self::type_id,
		'data' => self::type_object,
		'width' => self::type_integer,
		'height' => self::type_integer,
		'mime_type' => self::type_string,
		'path' => self::type_string,
		'title' => self::type_string,
		'description' => self::type_string,
		'created' => self::type_created,
		'modified' => self::type_modified,
	];

	/**
	 *
	 * @var array
	 */
	public array $column_defaults = [
		'title' => '',
		'description' => '',
	];
}
