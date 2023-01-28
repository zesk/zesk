<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage webapp
 * @author kent
 * @copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk\WebApp;

/**
 * @see Class_Cluster
 * @author kent
 * @property integer $id
 * @property string $name
 * @property string $code
 * @property string $sitecode
 * @property integer $min_members
 * @property integer $max_members
 * @property \zesk\Timestamp $active
 */
class Cluster extends ORMBase {
	public static function find_from_site(Site $site) {
		return $site->application->ormFactory(self::class)->find([
			'sitecode' => $site->code,
		]);
	}
}
