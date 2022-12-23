<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage objects
 */
namespace zesk\World;

use zesk\Application;
use zesk\Database_Exception_SQL;
use zesk\Exception_Configuration;
use zesk\Exception_Key;
use zesk\Exception_Parameter;
use zesk\Exception_Semantics;
use zesk\Exception_Unimplemented;
use zesk\ORM\Exception_ORMEmpty;
use zesk\ORM\ORMBase;
use zesk\ORM\Exception_ORMNotFound;

/**
 * @author kent
 * @see Class_Country
 * @property int $id
 * @property string $code
 * @property string $name
 */
class Country extends ORMBase {
	/**
	 * @param Application $application
	 * @param string|int $mixed
	 * @return self
	 * @throws Exception_Configuration
	 * @throws Exception_Key
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Parameter
	 * @throws Exception_Semantics
	 * @throws Database_Exception_SQL
	 * @throws Exception_Unimplemented
	 * @throws Exception_ORMEmpty
	 */
	public static function findCountry(Application $application, string|int $mixed): self {
		if (is_numeric($mixed)) {
			$c = new Country($application, $mixed);
			return $c->fetch();
		} else {
			$c = new Country($application, [
				'code' => $mixed,
			]);
			return $c->find();
		}
	}
}
