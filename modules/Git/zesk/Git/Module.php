<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage git
 * @author kent
 * @copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk\Git;

use zesk\Repository\ModuleBase;

/**
 *
 * Basically registers our class and leaves the rest to parent class.
 *
 * @author kent
 */
class Module extends ModuleBase {
	public function initialize(): void {
		parent::initialize();
		$this->registerRepository(Repository::class, [
			'git',
		]);
	}
}
