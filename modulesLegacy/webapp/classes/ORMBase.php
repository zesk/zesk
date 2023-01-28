<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage webapp
 * @author kent
 * @copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk\WebApp;

use zesk\ORM\ORMBase as zeskORMBase;

/**
 * @see Class_Base
 * @author kent
 *
 */
class ORMBase extends zeskORMBase {
	/**
	 * @return Module
	 */
	public function webappModule(): Module {
		return $this->application->webappModule();
	}
}
