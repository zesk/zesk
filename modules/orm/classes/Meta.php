<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 * @see Class_Meta
 * @author kent
 *
 */
class Meta extends ORM {
	/**
	 *
	 * @var boolean
	 */
	protected $_meta_fetch = false;

	/**
	 *
	 * @param Application $application
	 * @param string $name
	 * @return self
	 */
	protected static function class_meta_factory($class, ORM $parent, $name) {
		$name = self::clean_code_name($name, "_");
		return $parent->application->orm_factory($class, [
			"parent" => $parent,
			"name" => $name,
		]);
	}

	/*
	 * In child classes, use this for factory
	 *
	 public static function meta_factory(Account $parent, $name) {
	 return parent::class_meta_factory(__CLASS__, $parent, $name);
	 }
	 */

	/**
	 *
	 * @param mixed $value
	 * @return self
	 */
	public function meta_set($value) {
		$this->_meta_fetch = true;
		return $this->set_member("value", $value)->store();
	}

	/**
	 *
	 * @param unknown $default
	 * @return mixed|array
	 */
	public function meta_get($default = null) {
		if (!$this->_meta_fetch) {
			try {
				$this->fetch();
			} catch (Exception_ORM_NotFound $e) {
			}
			$this->_meta_fetch = true;
		}
		return $this->member("value", $default);
	}
}
