<?php
/**
 *
 */
namespace zesk\Subversion;

use zesk\Command_Configure;
use zesk\Directory;

/**
 *
 * @author kent
 *
 */
class Module extends \zesk\Module_Repository {
	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Module::initialize()
	 */
	function initialize() {
		parent::initialize();
		$this->register_repository(Repository::class, array(
			"svn",
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
		$repo = array_shift($arguments);
		$target = $this->application->paths->expand(array_shift($arguments));
		$__ = compact("repo", "target");
		try {
			if (!is_dir($target)) {
				if (!$command->prompt_yes_no(__("Create subversion directory {target} for {repo}", $__))) {
					return false;
				}
				if (!Directory::create($target)) {
					$command->error(__("Unable to create {target}", $__));
					return false;
				}
				$command->verbose_log("Created {target}", $__);
			}
			$config_dir = $app->paths->home(".subversion");
			$command->verbose_log("Subversion configuration path is {config_dir}", compact("config_dir"));
			if (!is_dir(path($target, ".svn"))) {
				if (!$command->prompt_yes_no(__("Checkout subversion {repo} to {target}", $__))) {
					return false;
				}
				$app->process->execute_arguments("svn --non-interactive --config-dir {0} co {1} {2}", array(
					$config_dir,
					$repo,
					$target
				), true);
				return true;
			} else {
				$results = $app->process->execute_arguments("svn --non-interactive --config-dir {0} status --show-updates {1}", array(
					$config_dir,
					$target
				));
				if (count($results) > 1) {
					$command->log($results);
					if (!$command->prompt_yes_no(__("Update subversion {target} from {repo}", $__))) {
						return false;
					}
					$app->process->execute_arguments("svn --non-interactive --config-dir {0} up --force {1}", array(
						$config_dir,
						$target
					), true);
				}
			}
			return true;
		} catch (\Exception $e) {
			$command->error("Command failed: {e}", compact("e"));
			return false;
		}
	}
}