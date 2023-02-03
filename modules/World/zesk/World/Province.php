<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage objects
 * @see Class_Province
 */
namespace zesk\World;

use zesk\ORM\ORMBase;

/**
 * @see Class_Province
 * @property int $id
 * @property string $code
 * @property Country $country
 * @property string $name
 */
class Province extends ORMBase {
	public const MEMBER_ID = 'id';

	public const MEMBER_CODE = 'code';

	public const MEMBER_COUNTRY = 'country';

	public const MEMBER_NAME = 'name';
}
