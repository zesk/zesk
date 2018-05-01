<?php
/**
 *
 */
namespace zesk\Subversion;

use zesk\Command_Configure;
use zesk\Directory;
use zesk\Exception_System;

/**
 *
 * @author kent
 *
 */
class Module extends \zesk\Module_Repository {
	const TYPE = "svn";

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Module::initialize()
	 */
	function initialize() {
		$required_class = "SimpleXMLElement";
		if (!class_exists($required_class, false)) {
			throw new Exception_System("{class} requires the {required_class}. See {help_url}", array(
				"class" => get_class($this),
				"required_class" => $required_class,
				"help_url" => "http://php.net/manual/en/simplexml.installation.php"
			));
		}
		parent::initialize();
		$this->register_repository(Repository::class, array(
			self::TYPE,
			"subversion"
		));
		$this->application->hooks->add(Command_Configure::class . '::command_subversion', array(
			$this,
			"command_subversion"
		));
	}

	/**
	 * Support configuration command for subversion
	 *
	 * @see Command_Configure
	 * @param Command_Configure $command
	 */
	public function command_subversion(Command_Configure $command, array $arguments = array(), $command_name) {
		$app = $command->application;
		$url = array_shift($arguments);
		$target = $this->application->paths->expand(array_shift($arguments));
		$__ = compact("url", "target");
		try {
			if (!is_dir($target)) {
				if (!$command->prompt_yes_no(__("Create subversion directory {target} for {url}", $__))) {
					return false;
				}
				if (!Directory::create($target)) {
					$command->error(__("Unable to create {target}", $__));
					return false;
				}
				$command->verbose_log("Created {target}", $__);
			}
			$repo = Repository::factory($this->application, self::TYPE, $target);
			$repo->url($url);
			if (!$repo->need_update()) {
				if ($repo->need_commit()) {
					$command->log("Repository at {target} has uncommitted changes");
					$command->log(array_keys($repo->status()));
				}
				return null;
			}
			if (!$command->prompt_yes_no(__("Update subversion {target} from {url}", $__))) {
				return false;
			}
			$repo->update();
			return true;
		} catch (\Exception $e) {
			$command->error("Command failed: {e}", compact("e"));
			return false;
		}
	}
}
