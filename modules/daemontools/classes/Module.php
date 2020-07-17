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
use zesk\ArrayTools;
use zesk\Configure\Engine;
use zesk\File;
use zesk\Directory;
use zesk\Server;
use zesk\Timestamp;

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
		$this->application->hooks->add(Engine::class . '::command_daemontools_service', array(
			$this,
			"command_daemontools_service",
		));
		$this->application->hooks->add(Engine::class . '::command_daemontools_service_remove', array(
			$this,
			"command_daemontools_service_remove",
		));
		$this->application->theme_path($this->path("theme/system"), "system");
		$this->application->theme_path($this->path("theme/service"), "zesk/daemontools/service");
	}

	/**
	 *
	 * @param Template $template
	 * @return string[][]
	 */
	protected function hook_system_panel() {
		return array(
			"system/panel/daemontools" => array(
				"title" => $this->application->locale->__("DaemonTools Processes"),
				"module_class" => __CLASS__,
			),
		);
	}

	/**
	 * Hook for daemontools_service source_path [service_name]
	 *
	 * @param Engine $command
	 */
	public function command_daemontools_service(Engine $command, array $arguments = array(), $command_name) {
		$source = avalue($arguments, 0);
		if ($source === "--help") {
			return $this->command_daemontools_service_help($command_name);
		}
		$service_name = avalue($arguments, 1);
		if (!is_dir($source)) {
			$command->error("{command_name} {source} should be a directory", array(
				"source" => $source,
				"command_name" => $command_name,
			));
			return false;
		}
		if (!empty($service_name)) {
			$service_name = File::clean_path($service_name);
		} else {
			$service_name = basename(trim($source, "/"));
		}
		$target = $this->services_path($service_name);
		$command->verbose_log("Service target is {target}", array(
			"target" => $target,
		));
		$changed = false;
		foreach (array(
			"run",
			"log/run",
		) as $f) {
			$source_file = path($source, $f);
			$target_file = path($target, $f);
			if (is_file($source_file)) {
				$result = $command->command_mkdir(dirname($target_file), "root:root", "0755");
				if ($result === false) {
					return false;
				} elseif ($result === true) {
					$changed = true;
				}
				$result = $command->command_file($source_file, $target_file, "root:root", "0744");
				if ($result === false) {
					return false;
				} elseif ($result === true) {
					$changed = true;
				}
			}
		}
		if ($changed) {
			if ($command->prompt_yes_no("Restart service $target?")) {
				$command->exec("svc -t {target}", array(
					"target" => $target,
				));
				$command->log("{target} restarted", array(
					"target" => $target,
				));
			}
			return true;
		}
		return null;
	}

	/**
	 * Help for daemontools_service
	 *
	 * @param string $command_name
	 * @return string[]
	 */
	public function command_daemontools_service_help($command_name) {
		return $this->application->locale->__(array(
			"command_syntax" => "$command_name source [service_name]",
			"arguments" => array(
				"source" => "Directory which contains a file \"run\" which is the service run command and optionally a log/run for logging.",
				"service_name" => "The name of the service to create. Uses basename of source if not supplied.",
			),
			"description" => "Create (or update) a daemontools service",
		));
	}

	/**
	 * Hook for daemontools_service_remove
	 *
	 * @param Engine $command
	 */
	public function command_daemontools_service_remove(Engine $command, array $arguments = array(), $command_name) {
		$service_name = avalue($arguments, 0);
		if ($service_name === "--help") {
			return $this->command_daemontools_service_remove_help($command_name);
		}
		$service_name = File::clean_path($service_name);
		$target = $this->services_path($service_name);
		$__ = array(
			"target" => $target,
			"command_name" => $command_name,
		);
		$changed = null;
		if (!is_dir($target)) {
			$command->verbose_log("{command_name} {target} - target does not exist, done", $__);
			return $changed;
		}
		$locale = $this->application->locale;
		$command->verbose_log("{command_name} {target} exists", $__);
		foreach (array(
			$target,
			path($target, "log"),
		) as $service) {
			if (is_dir($service)) {
				$command->log($command->exec("svstat {target}", $__));
				$__['service'] = $service;
				if ($command->prompt_yes_no($locale->__("Terminate service {service} and supervise process? ", $__), true)) {
					$this->application->process->debug = true;
					$command->exec("svc -dx {service}", $__);
					$changed = true;
				}
			} else {
				$command->verbose_log("Terminating service {target}", $__);
			}
		}
		if ($command->prompt_yes_no($locale->__("Delete {target}? ", $__), true)) {
			return Directory::delete($target);
		}
		return $changed;
	}

	/**
	 * Help for daemontools_service_remove
	 *
	 * @param string $command_name
	 * @return string[]
	 */
	public function command_daemontools_service_remove_help($command_name) {
		return $this->application->locale->__(array(
			"command_syntax" => "$command_name source",
			"arguments" => array(
				"source" => "Name of service as found in /etc/service/[source]",
			),
			"description" => "Remove a daemontools service permanently",
		));
	}

	/**
	 * Services path for Daemontools
	 *
	 * @return string
	 */
	public function services_path($add = null) {
		return path($this->option("services_path", "/etc/service"), $add);
	}

	/**
	 *
	 * @return \zesk\DaemonTools\Service[]
	 */
	public function services() {
		$names = $this->list_service_names();
		$svstat_names = $unreadable_names = array();
		foreach ($names as $name) {
			$path = $this->services_path($name);
			if (is_readable(path($path, "supervise/status"))) {
				$svstat_names[] = $path;
			} else {
				$unreadable_names[] = $path;
			}
		}
		$services = array();
		if (count($svstat_names) > 0) {
			foreach ($this->application->process->execute_arguments("svstat {*}", $svstat_names) as $line) {
				$services[] = Service::from_svstat($this->application, $line);
			}
		}
		if (count($unreadable_names) > 0) {
			foreach ($unreadable_names as $path) {
				$stat_helper = path($path, ".svstat");
				$this->application->logger->debug("Loading {path}", array(
					"path" => $stat_helper,
				));
				if (is_readable($stat_helper)) {
					$services[] = Service::from_svstat($this->application, file_get_contents($stat_helper))->set_option("mtime", filemtime($stat_helper));
				}
			}
		}
		return $services;
	}

	/**
	 * Save data for dashboard
	 */
	public function hook_cron() {
		$this->save_services_snapshot(Server::singleton($this->application), $this->services());
	}

	/**
	 * Save services snapshot to server data
	 *
	 * @param Server $server
	 * @param Service[] $services
	 */
	public function save_services_snapshot(Server $server, array $services) {
		$snapshot = array();
		foreach ($services as $service) {
			$snapshot[] = $service->variables();
		}
		$server->data(__CLASS__, $snapshot);
		$server->data(__CLASS__ . "::last_updated", Timestamp::now());
	}

	/**
	 * For testing, generate some data
	 *
	 */
	public function mock_server_snapshot() {
		$app = $this->application;
		$this->save_services_snapshot(Server::singleton($app), array(
			Service::instance($app, "/etc/service/fake", array(
				"status" => "up",
				"ok" => true,
				"pid" => 1234,
				"duration" => 100,
			)),
			Service::instance($app, "/etc/service/not-real", array(
				"status" => "down",
				"ok" => true,
				"duration" => 100,
			)),
			Service::instance($app, "/etc/service/imaginary", array(
				"status" => "down",
				"ok" => false,
			)),
		));
	}

	/**
	 *
	 * @param Server $object
	 * @return Service[]
	 */
	public function server_services(Server $object) {
		$data = $object->data(__CLASS__);
		if (!can_iterate($data)) {
			return null;
		}
		$result = array();
		foreach ($data as $variables) {
			$result[] = Service::from_variables($this->application, $variables);
		}
		return $result;
	}

	/**
	 *
	 * @param Server $object
	 * @return Timestamp
	 */
	public function server_services_last_updated(Server $object) {
		$result = $object->data(__CLASS__ . "::last_updated");
		return $result;
	}

	/**
	 *
	 */
	public function list_service_names() {
		$files = Directory::list_recursive($this->services_path(), array(
			"rules_file" => array(
				'#/run$#' => true,
				false,
			),
			"rules_directory" => false,
			"rules_directory_walk" => array(
				'#/\\.#' => false,
				true,
			),
		));
		return ArrayTools::unsuffix($files, "/run");
	}
}
