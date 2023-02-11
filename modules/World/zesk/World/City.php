<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage objects
 */
namespace zesk\World;

use zesk\ORM\ORMBase;

/**
 * @see Class_City
 */
class City extends ORMBase {
	public const MEMBER_ID = 'id';

	public const MEMBER_NAME = 'name';

	public const MEMBER_COUNTY = 'county';

	public const MEMBER_PROVINCE = 'province';
}
