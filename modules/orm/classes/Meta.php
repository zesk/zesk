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
	protected bool $_meta_fetch = false;

	/**
	 *
	 * @param string $name
	 * @return self
	 */
	protected static function classMetaFactory(string $class, ORM $parent, string $name) {
		$name = self::clean_code_name($name, '_');
		return $parent->application->orm_factory($class, [
			'parent' => $parent,
			'name' => $name,
		]);
	}

	/*
	 * In child classes, use this for factory
	 *
	 public static function metaFactory(Account $parent, $name) {
	 return parent::classMetaFactory(__CLASS__, $parent, $name);
	 }
	 */

	/**
	 *
	 * @param mixed $value
	 * @return self
	 */
	public function metaSet(string $value): self {
		$this->_meta_fetch = true;
		return $this->setMember('value', $value)->store();
	}

	/**
	 *
	 * @param string $default
	 * @return string
	 */
	public function metaGet(string $default = ''): string {
		if (!$this->_meta_fetch) {
			try {
				$this->fetch();
			} catch (Exception_ORM_NotFound|Exception_ORM_Empty) {
			}
			$this->_meta_fetch = true;
		}
		return $this->member('value', $default);
	}
}
