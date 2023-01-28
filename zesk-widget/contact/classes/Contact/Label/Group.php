<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage contact
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk;

class Contact_Label_Group extends ORMBase {
	public static function register_group(Application $app, $name) {
		if (empty($name)) {
			return null;
		}
		$g = $app->ormFactory(__CLASS__, [
			'Name' => $name,
		]);
		$g->register();
		return $g;
	}
}
