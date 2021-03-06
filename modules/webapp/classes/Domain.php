<?php
/**
 * @package zesk
 * @subpackage webapp
 * @author kent
 * @copyright &copy; 2019 Market Acumen, Inc.
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
 * @property \zesk\ORM $target
 * @property \zesk\Timestamp $accessed
 * @property boolean $active
 */
class Domain extends ORM {
	public function accessed() {
		$this->query_update()
			->value("accessed", Timestamp::now())
			->where("id", $this->id())
			->execute();
		return $this;
	}
}
