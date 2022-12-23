<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage webapp
 * @author kent
 * @copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk\WebApp;

use zesk\Timestamp;

/**
 *
 * @author kent
 * @see Class_Domain
 * @property integer $id
 * @property string $name
 * @property string $type
 * @property \zesk\ORMBase $target
 * @property \zesk\Timestamp $accessed
 * @property boolean $active
 */
class Domain extends ORM {
	public function accessed() {
		$this->queryUpdate()
			->value('accessed', Timestamp::now())
			->addWhere('id', $this->id())
			->execute();
		return $this;
	}
}
