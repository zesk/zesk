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
		'id' => self::TYPE_ID,
		'data' => self::TYPE_OBJECT,
		'width' => self::TYPE_INTEGER,
		'height' => self::TYPE_INTEGER,
		'mime_type' => self::TYPE_STRING,
		'path' => self::TYPE_STRING,
		'title' => self::TYPE_STRING,
		'description' => self::TYPE_STRING,
		'created' => self::TYPE_CREATED,
		'modified' => self::TYPE_MODIFIED,
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
