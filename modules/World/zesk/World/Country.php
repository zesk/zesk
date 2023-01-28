<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage objects
 */

namespace zesk\World;

use zesk\Application;
use zesk\Database_Exception_Connect;
use zesk\Exception;
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
	public const MEMBER_ID = 'id';

	public const MEMBER_CODE = 'code';

	public const MEMBER_NAME = 'name';

	/**
	 * @param Application $application
	 * @param string|int $mixed
	 * @return self
	 * @throws Exception_ORMNotFound
	 * @throws Database_Exception_Connect
	 */
	public static function findCountry(Application $application, string|int $mixed): self {
		try {
			if (is_numeric($mixed)) {
				$c = new Country($application, $mixed);
				return $c->fetch();
			} else {
				$c = new Country($application, [
					self::MEMBER_CODE => $mixed,
				]);
				$country = $c->find();
				assert($country instanceof self);
				return $country;
			}
		} catch (Database_Exception_Connect|Exception_ORMNotFound $e) {
			throw $e;
		} catch (Exception $e) {
			throw new Exception_ORMNotFound(self::class, $e->getMessage(), $e->variables(), $e);
		}
	}
}
