<?php
/**
 * @package zesk
 * @subpackage git
 * @author kent
 * @copyright &copy; 2017 Market Acumen, Inc.
 */
namespace zesk\Git;

/**
 * @author kent
 */
class Module extends \zesk\Module_Repository {
	function initialize() {
		parent::initialize();
		$this->register_repository(Repository::class, array(
			"git"
		));
	}
}