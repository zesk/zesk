<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage user
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Preference;

use Throwable;
use zesk\Application;
use zesk\ORM\ORMNotFound;
use zesk\ORM\ORMBase;

/**
 * @see Value
 * @see Type
 * @see Class_Value
 * @see Class_Type
 * @author kent
 *
 */
class Type extends ORMBase
{
	public const MEMBER_CODE = 'code';

	/**
	 * Find a preference type with the given name
	 *
	 * @param Application $application
	 * @param string $code_name
	 * @param string $name
	 * @return self
	 * @throws ORMNotFound
	 */
	public static function registerName(Application $application, string $code_name, string $name = ''): self
	{
		try {
			$result = $application->ormFactory(__CLASS__, [
				'name' => $name ?: $code_name, 'code' => $code_name,
			])->register();
			assert($result instanceof self);
			return $result;
		} catch (Throwable $e) {
			throw new ORMNotFound(self::class, 'Can not register {code_name} ({name})', [
				'code_name' => $code_name, 'name' => $name,
			], $e);
		}
	}
}
