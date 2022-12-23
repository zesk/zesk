<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage daemontools
 * @author kent
 * @copyright &copy; 2022, Market Acumen, Inc.
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
	public function initialize(): void {
		parent::initialize();
		$this->application->hooks->add(Engine::class . '::command_daemontools_service', [
			$this,
			'command_daemontools_service',
		]);
		$this->application->hooks->add(Engine::class . '::command_daemontools_service_remove', [
			$this,
			'command_daemontools_service_remove',
		]);
		$this->application->addThemePath($this->path('theme/system'), 'system');
		$this->application->addThemePath($this->path('theme/service'), 'zesk/daemontools/service');
	}

	/**
	 *
	 * @param Template $template
	 * @return string[][]
	 */
	protected function hook_system_panel() {
		return [
			'system/panel/daemontools' => [
				'title' => $this->application->locale->__('DaemonTools Processes'),
				'module_class' => __CLASS__,
			],
		];
	}

	/**
	 * Hook for daemontools_service source_path [service_name]
	 *
	 * @param Engine $command
	 */
	public function command_daemontools_service(Engine $command, array $arguments, $command_name) {
		$source = $arguments[0] ?? null;
		if ($source === '--help') {
			return $this->command_daemontools_service_help($command_name);
		}
		$service_name = $arguments[1] ?? null;
		if (!is_dir($source)) {
			$command->error('{command_name} {source} should be a directory', [
				'source' => $source,
				'command_name' => $command_name,
			]);
			return false;
		}
		if (!empty($service_name)) {
			$service_name = File::clean_path($service_name);
		} else {
			$service_name = basename(trim($source, '/'));
		}
		$target = $this->services_path($service_name);
		$command->verboseLog('Service target is {target}', [
			'target' => $target,
		]);
		$changed = false;
		foreach ([
			'run',
			'log/run',
		] as $f) {
			$source_file = path($source, $f);
			$target_file = path($target, $f);
			if (is_file($source_file)) {
				$result = $command->command_mkdir(dirname($target_file), 'root:root', '0755');
				if ($result === false) {
					return false;
				} elseif ($result === true) {
					$changed = true;
				}
				$result = $command->command_file($source_file, $target_file, 'root:root', '0744');
				if ($result === false) {
					return false;
				} elseif ($result === true) {
					$changed = true;
				}
			}
		}
		if ($changed) {
			if ($command->prompt_yes_no("Restart service $target?")) {
				$command->exec('svc -t {target}', [
					'target' => $target,
				]);
				$command->log('{target} restarted', [
					'target' => $target,
				]);
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
		return $this->application->locale->__([
			'command_syntax' => "$command_name source [service_name]",
			'arguments' => [
				'source' => 'Directory which contains a file "run" which is the service run command and optionally a log/run for logging.',
				'service_name' => 'The name of the service to create. Uses basename of source if not supplied.',
			],
			'description' => 'Create (or update) a daemontools service',
		]);
	}

	/**
	 * Hook for daemontools_service_remove
	 *
	 * @param Engine $command
	 */
	public function command_daemontools_service_keysRemove(Engine $command, array $arguments, $command_name) {
		$service_name = $arguments[0] ?? null;
		if ($service_name === '--help') {
			return $this->command_daemontools_service_remove_help($command_name);
		}
		$service_name = File::clean_path($service_name);
		$target = $this->services_path($service_name);
		$__ = [
			'target' => $target,
			'command_name' => $command_name,
		];
		$changed = null;
		if (!is_dir($target)) {
			$command->verboseLog('{command_name} {target} - target does not exist, done', $__);
			return $changed;
		}
		$locale = $this->application->locale;
		$command->verboseLog('{command_name} {target} exists', $__);
		foreach ([
			$target,
			path($target, 'log'),
		] as $service) {
			if (is_dir($service)) {
				$command->log($command->exec('svstat {target}', $__));
				$__['service'] = $service;
				if ($command->prompt_yes_no($locale->__('Terminate service {service} and supervise process? ', $__), true)) {
					$this->application->process->debug = true;
					$command->exec('svc -dx {service}', $__);
					$changed = true;
				}
			} else {
				$command->verboseLog('Terminating service {target}', $__);
			}
		}
		if ($command->prompt_yes_no($locale->__('Delete {target}? ', $__), true)) {
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
		return $this->application->locale->__([
			'command_syntax' => "$command_name source",
			'arguments' => [
				'source' => 'Name of service as found in /etc/service/[source]',
			],
			'description' => 'Remove a daemontools service permanently',
		]);
	}

	/**
	 * Services path for Daemontools
	 *
	 * @return string
	 */
	public function services_path($add = null) {
		return path($this->option('services_path', '/etc/service'), $add);
	}

	/**
	 *
	 * @return \zesk\DaemonTools\Service[]
	 */
	public function services() {
		$names = $this->list_service_names();
		$svstat_names = $unreadable_names = [];
		foreach ($names as $name) {
			$path = $this->services_path($name);
			if (is_readable(path($path, 'supervise/status'))) {
				$svstat_names[] = $path;
			} else {
				$unreadable_names[] = $path;
			}
		}
		$services = [];
		if (count($svstat_names) > 0) {
			foreach ($this->application->process->executeArguments('svstat {*}', $svstat_names) as $line) {
				$services[] = Service::fromServiceStatusLine($this->application, $line);
			}
		}
		if (count($unreadable_names) > 0) {
			foreach ($unreadable_names as $path) {
				$stat_helper = path($path, '.svstat');
				$this->application->logger->debug('Loading {path}', [
					'path' => $stat_helper,
				]);
				if (is_readable($stat_helper)) {
					$services[] = Service::fromServiceStatusLine($this->application, file_get_contents($stat_helper))->setOption('mtime', filemtime($stat_helper));
				}
			}
		}
		return $services;
	}

	/**
	 * Save data for dashboard
	 */
	public function hook_cron(): void {
		$this->save_services_snapshot(Server::singleton($this->application), $this->services());
	}

	/**
	 * Save services snapshot to server data
	 *
	 * @param Server $server
	 * @param Service[] $services
	 */
	public function save_services_snapshot(Server $server, array $services): void {
		$snapshot = [];
		foreach ($services as $service) {
			$snapshot[] = $service->variables();
		}
		$server->data(__CLASS__, $snapshot);
		$server->data(__CLASS__ . '::last_updated', Timestamp::now());
	}

	/**
	 * For testing, generate some data
	 *
	 */
	public function mock_server_snapshot(): void {
		$app = $this->application;
		$this->save_services_snapshot(Server::singleton($app), [
			Service::instance($app, '/etc/service/fake', [
				'status' => 'up',
				'ok' => true,
				'pid' => 1234,
				'duration' => 100,
			]),
			Service::instance($app, '/etc/service/not-real', [
				'status' => 'down',
				'ok' => true,
				'duration' => 100,
			]),
			Service::instance($app, '/etc/service/imaginary', [
				'status' => 'down',
				'ok' => false,
			]),
		]);
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
		$result = [];
		foreach ($data as $variables) {
			$result[] = Service::fromVariables($this->application, $variables);
		}
		return $result;
	}

	/**
	 *
	 * @param Server $object
	 * @return Timestamp
	 */
	public function server_services_last_updated(Server $object) {
		$result = $object->data(__CLASS__ . '::last_updated');
		return $result;
	}

	/**
	 *
	 */
	public function list_service_names() {
		$files = Directory::list_recursive($this->services_path(), [
			'rules_file' => [
				'#/run$#' => true,
				false,
			],
			'rules_directory' => false,
			'rules_directory_walk' => [
				'#/\\.#' => false,
				true,
			],
		]);
		return ArrayTools::valuesRemoveSuffix($files, '/run');
	}
}
