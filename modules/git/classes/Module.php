<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage git
 * @author kent
 * @copyright &copy; 2022 Market Acumen, Inc.
 */
namespace zesk\Git;

/**
 * Basically registers our class and leaves the rest to parent class.
 *
 * @author kent
 */
class Module extends \zesk\Module_Repository {
	public function initialize(): void {
		parent::initialize();
		$this->register_repository(Repository::class, [
			'git',
		]);
	}
}
