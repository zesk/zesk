<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage contact
 * @author kent
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
namespace zesk;

class Contact_Label_Group extends ORM {
	public static function register_group(Application $app, $name) {
		if (empty($name)) {
			return null;
		}
		$g = $app->orm_factory(__CLASS__, [
			"Name" => $name,
		]);
		$g->register();
		return $g;
	}
}
