<?php

namespace zesk;

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
class Tag_Label extends Object {
	/**
	 *
	 * @return Cache
	 */
	private static function label_cache() {
		return Cache::register(__CLASS__);
	}
	
	/**
	 *
	 * @param unknown $code        	
	 * @return zesk\Tag_Label
	 */
	public static function label_find($code) {
		$members = array();
		$members['code'] = self::clean_code_name($code);
		return self::factory(__CLASS__, $members)->find();
	}
	/**
	 *
	 * @param string $name        	
	 * @param array $attributes        	
	 * @return zesk\Tag_Label
	 */
	public static function label_register($name = null, array $attributes = array()) {
		$cache = self::label_cache();
		$members = arr::filter($attributes, "code;is_internal;is_translated;owner") + array(
			"code" => $name,
			"name" => $name
		);
		$members['code'] = self::clean_code_name($members['code']);
		if ($cache->has($members['code'])) {
			$object = $cache->get($members['code']);
			if ($object instanceof Tag_Label) {
				$object->seen();
				return $object;
			}
		}
		$object = self::factory(__CLASS__, $members)->register();
		$cache->set($members['code'], $object);
		$object->seen();
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
