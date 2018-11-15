<?php
/**
 * @package zesk
 * @subpackage git
 * @author kent
 * @copyright &copy; 2017 Market Acumen, Inc.
 */
namespace zesk\Git;

/**
 * Basically registers our class and leaves the rest to parent class.
 *
 * @author kent
 */
class Module extends \zesk\Module_Repository {
    public function initialize() {
        parent::initialize();
        $this->register_repository(Repository::class, array(
            "git",
        ));
    }
}
