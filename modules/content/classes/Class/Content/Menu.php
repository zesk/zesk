<?php declare(strict_types=1);
namespace zesk;

/**
 * @see Content_Menu
 * @author kent
 *
 */
class Class_Content_Menu extends Class_ORM {
	public array $find_keys = [
		"code",
	];

	public string $id_column = "id";
}
