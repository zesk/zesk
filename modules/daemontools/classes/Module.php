<?php
/**
 * @package zesk
 * @subpackage daemontools
 * @author kent
 * @copyright &copy; 2018 Market Acumen, Inc.
 */
namespace zesk\DaemonTools;

/**
 *
 */
use zesk\arr;
use zesk\Command_Configure;
use zesk\File;
use zesk\Directory;

/**
 * @author kent
 */
class Module extends \zesk\Module {

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Module::initialize()
	 */
	public function initialize() {
		parent::initialize();
		$this->hooks->add('zesk\\Command_Configure::command_daemontools_service', array(
			$this,
			"command_daemontools_service"
		));
	}
	/**
	 * Hook for daemontools_service
	 *
	 * @param Command_Configure $command
	 */
	public function command_daemontools_service(Command_Configure $command, array $arguments = array(), $command_name) {
		$source = avalue($arguments, 0);
		$service_name = avalue($arguments, 1);
		if (!is_dir($source)) {
			$command->error("{command_name} {source} should be a directory", array(
				"source" => $source,
				"command_name" => $command_name
			));
			return false;
		}
		if (!empty($service_name)) {
			$service_name = File::clean_path($service_name);
		} else {
			$service_name = basename(trim($source, "/"));
		}
		$target = "/etc/service/$service_name";
		$command->verbose_log("Service target is {target}", array(
			"target" => $target
		));
		$changed = false;
		foreach (array(
			"run",
			"log/run"
		) as $f) {
			$source_file = path($source, $f);
			$target_file = path($target, $f);
			if (is_file($source_file)) {
				$result = $command->command_mkdir(dirname($target_file), "root:root", 0700);
				if ($result === false) {
					return false;
				} else if ($result === true) {
					$changed = true;
				}
				$result = $command->command_file($source_file, $target_file, "root:root", 0700);
				if ($result === false) {
					return false;
				} else if ($result === true) {
					$changed = true;
				}
			}
		}
		if ($changed) {
			if ($command->prompt_yes_no("Restart service $target?")) {
				$command->exec("svc -t {target}", array(
					"target" => $target
				));
				$command->log("{target} restarted", array(
					"target" => $target
				));
			}
			return true;
		}
		return null;
	}
	/**
	 * Hook for daemontools_service_remove
	 *
	 * @param Command_Configure $command
	 */
	public function command_daemontools_service_remove(Command_Configure $command, array $arguments = array(), $command_name) {
		$service_name = avalue($arguments, 0);
		$service_name = File::clean_path($service_name);
		$target = "/etc/service/$service_name";
		$__ = array(
			"target" => $target,
			"command_name" => $command_name
		);
		$changed = null;
		if (!is_dir($target)) {
			$command->verbose_log("{command_name} {target} - target does not exist, done", $__);
			return $changed;
		}
		$command->verbose_log("{command_name} {target} exists", $__);
		foreach (array(
			$target,
			path($target, "log")
		) as $service) {
			if (is_dir($service)) {
				$command->log($command->exec("svstat {target}", $__));
				$__['service'] = $service;
				if ($command->prompt_yes_no(__("Terminate service {service} and supervise process? ", $__), true)) {
					$this->application->process->debug = true;
					$command->exec("svc -dx {service}", $__);
					$changed = true;
				}
			} else {
				$this->verbose_log("Terminating service {target}", $__);
			}
		}
		if ($command->prompt_yes_no(__("Delete {target}? ", $__), true)) {
			return Directory::delete($target);
		}
		return $changed;
	}

	/**
	 * Services path for Daemontools
	 *
	 * @return string
	 */
	public function services_path() {
		return $this->option("services_path", "/etc/service");
	}

	/**
	 *
	 */
	public function list_services() {
		$files = Directory::list_recursive($this->services_path(), array(
			"rules_file" => array(
				'#/run$#' => true,
				false
			),
			"rules_directory" => false,
			"rules_directory_walk" => array(
				'#/\\.#' => false,
				true
			)
		));
		return arr::unsuffix($files, "/run");
	}
}