<?php declare(strict_types=1);

/**
 *
 */
namespace zesk;

/**
 * Base class
 *
 * @author kent
 * @see Content_Article
 */
class Class_Content_Article extends Class_Base {
	public string $id_column = 'id';

	public $name_column = 'title';

	public $options = [
		'order_column' => 'order_index',
	];
}
