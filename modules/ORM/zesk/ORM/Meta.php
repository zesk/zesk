<?php
declare(strict_types=1);
/**
 *
 */
namespace zesk\ORM;

/**
 * @see Class_Meta
 * @author kent
 * @property ORMBase $parent
 * @property string $value
 */
class Meta extends ORMBase {
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
	protected static function classMetaFactory(string $class, ORMBase $parent, string $name): ORMBase {
		$name = self::clean_code_name($name, '_');
		$ormBase = $parent->application->ormFactory($class, [
			'parent' => $parent,
			'name' => $name,
		]);
		assert($ormBase instanceof ORMBase);
		return $ormBase;
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
	 * @return string
	 */
	public function metaGet(): string {
		if (!$this->_meta_fetch) {
			try {
				$this->fetch();
			} catch (Exception_ORM_NotFound|Exception_ORM_Empty) {
			}
			$this->_meta_fetch = true;
		}
		return $this->value;
	}
}
