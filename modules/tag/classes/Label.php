<?php
/**
 * @package zesk-modules
 * @subpackage tag
 * @author kent
 * @copyright &copy; 2018 Market Acumen, Inc.
 */
namespace zesk\Tag;

use zesk\Timestamp;
use zesk\Application;
use zesk\ArrayTools;

/**
 *
 * @see Class_Tag_Label
 * @see Tag
 * @author kent
 * @property id $id
 * @property string $code
 * @property string $name
 * @property boolean $is_internal
 * @property boolean $is_translated
 * @property User $owner
 * @property Timestamp $created
 * @property Timestamp $modified
 * @property timestamp $last_used
 */
class Label extends \zesk\ORM {
	/**
	 *
	 * @param unknown $code
	 * @return zesk\Tag_Label
	 */
	public static function label_find(Application $application, $code) {
		$members = array();
		$members['code'] = self::clean_code_name($code);
		return self::factory($application, __CLASS__, $members)->find();
	}
	/**
	 *
	 * @param string $name
	 * @param array $attributes
	 * @return zesk\Tag_Label
	 */
	public static function label_register(Application $application, $name = null, array $attributes = array()) {
		$tag_label = $application->orm_factory(__CLASS__);
		$members = ArrayTools::filter($attributes, "code;is_internal;is_translated;owner") + array(
			"code" => $name,
			"name" => $name
		);
		$members['code'] = self::clean_code_name($members['code']);
		$cache = $application->cache->getItem(__CLASS__ . "-" . $members['code']);
		if ($cache->isHit()) {
			$object = $cache->get();
			if ($object instanceof self) {
				$object->seen();
				return $object;
			}
		}
		$object = $application->orm_factory(__CLASS__, $members)->register();
		$object->seen();

		$cache->set($object);
		$application->cache->saveDeferred($cache);
		return $object;
	}

	/**
	 * Report this tag as being seen recently
	 */
	public function seen() {
		$this->last_seen = Timestamp::now();
		return $this->store();
	}
}