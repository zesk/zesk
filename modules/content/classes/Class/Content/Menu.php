<?php declare(strict_types=1);
namespace zesk;

/**
 * @see Content_Menu
 * @author kent
 *
 */
class Class_Content_Menu extends Class_ORM {
	public $find_keys = [
		"code",
	];

	public $id_column = "id";
}
