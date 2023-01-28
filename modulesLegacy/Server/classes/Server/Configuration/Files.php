<?php
declare(strict_types=1);

/**
 *
 * @version $URL: https://code.marketacumen.com/zesk/trunk/modules/server/classes/Server/Configuration/Files.php $
 * @package zesk
 * @subpackage server
 * @author $Author: kent $
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace Server\classes\Server\Configuration;

use Server\classes\Server\Server_Configuration;
use Server\classes\Server\Server_Feature;
use Server\classes\Server\Server_Platform;
use zesk\ArrayTools;
use zesk\Directory;
use zesk\Exception_Configuration;
use zesk\Exception_Directory_NotFound;
use zesk\Exception_File_NotFound;
use zesk\Exception_NotFound;
use zesk\File;
use zesk\PHP;
use zesk\StringTools;
use zesk\Text;

/**
 *
 * @author kent
 *
 */
class Server_Configuration_Files extends Server_Configuration {
	protected string $host_path = '';

	protected array $host_aliases = [];

	protected array $variables = [];

	public function __construct(Server_Platform $platform, $options = null) {
		parent::__construct($platform, $options);

		$this->_configure_host_path();
		$this->_load_aliases();
		$this->_load_configuration();
	}

	private function _configure_host_path(): void {
		$this->host_path = $this->option('host_path');
		if ($this->host_path) {
			$this->verboseLog('host_path is {host_path}', [
				'host_path' => $this->host_path,
			]);
			if (!is_dir($this->host_path)) {
				throw new Exception_Directory_NotFound($this->host_path);
			}
		} else {
			throw new Exception_Configuration('host_path', 'Must be set in Server_Configuration_Files');
		}
	}

	private function _load_aliases(): void {
		$this->host_aliases = [];

		try {
			$alias_file = File::lines(path($this->host_path, 'aliases'));
			$this->host_aliases = ArrayTools::listTrimClean(ArrayTools::keysTrim(ArrayTools::pairValues($alias_file), " \t"));
		} catch (Exception_File_NotFound $e) {
		}
	}

	private function _load_configuration(): void {
		$app = $this->application;
		$hostname = $this->platform->hostname();
		$alias_hostname = $this->host_aliases()[$hostname] ?? $hostname;
		if ($alias_hostname !== $hostname) {
			$app->logger->warning('Server_Configuration_Files _load_configuration: {hostname} aliaesed to {alias_hostname}', [
				'hostname' => $hostname, 'alias_hostname' => $alias_hostname,
			]);
		}
		$searched_paths = [];
		$this->search_path("all $alias_hostname");
		$files = $this->optionIterable('files', $app->configuration->get('server_configuration_file', 'environment.sh'));

		$search_path = $this->search_path();
		$result = [];
		while (count($searched_paths) < count($search_path)) {
			foreach ($search_path as $path) {
				if (array_key_exists($path, $searched_paths)) {
					continue;
				}
				foreach ($files as $file) {
					$conf = path($this->host_path, $path, $file);
					if (!is_file($conf)) {
						$app->logger->debug('No configuration file {conf}', [
							'conf' => $conf,
						]);

						continue;
					}
					$variables = $this->platform->conf_load($conf, [
						'variables' => $this->options, 'overwrite' => true,
					]);
					$result = $variables + $result;
					$this->setOptions($variables);
					$app->logger->debug("Loading configuration $conf");
				}
				$searched_paths[$path] = true;
			}
			$search_path = $this->search_path();
		}
		$app->logger->debug('configuration search path {paths}, files {files}, result {result}', [
			'paths' => implode(',', $search_path), 'files' => implode(',', $files), 'result' => PHP::dump($result),
		]);
	}

	public function feature_list() {
		return ArrayTools::listTrimClean($this->optionIterable('FEATURES', $this->optionIterable('SERVICES', [], ' '), ' '));
	}

	public function search_path($set = null) {
		if ($set !== null) {
			$this->setOption('SEARCH_PATH', $set);
			return $this;
		}
		return $this->optionIterable('SEARCH_PATH', [], ' ');
	}

	public function remote_package($url): void {
	}

	public function configure_feature(Server_Feature $feature): void {
		$shortname = $feature->code;
		$files = [
			path($feature->configure_path(), 'feature.conf'),
		];
		$files = array_merge($files, File::findAll($this->search_path(), path($shortname, 'feature.conf')));
		$settings = [];
		foreach ($files as $file) {
			if (is_file($file)) {
				$settings = $this->platform->conf_load($file) + $settings;
			}
		}
		$this->application->logger->debug('Configured feature {class} {settings}', [
			'class' => $feature::class, 'settings' => Text::format_pairs($settings),
		]);
		$feature->setOptions($settings);
	}

	final public function host_aliases(): array {
		if (is_array($this->host_aliases)) {
			return $this->host_aliases;
		}
		$this->host_aliases = [];
		if ($this->host_path === null) {
			return $this->host_aliases;
		}
		$alias_file = path($this->host_path, 'aliases');
		if (!file_exists($alias_file)) {
			return $this->host_aliases;
		}

		foreach (file($alias_file) as $line) {
			[$line] = pair(trim($line), '#', $line);
			$line = trim($line);
			if (empty($line)) {
				continue;
			}
			$name = $alias = null;
			[$name, $alias] = pair($line, ' ', $line);
			if ($alias === null) {
				continue;
			}
			$name = trim($name);
			$alias = trim($alias);
			if (array_key_exists($name, $this->host_aliases)) {
				$this->verboseLog("Alias file $alias_file defines $name twice");
			}
			$this->host_aliases[$name] = $alias;
		}
		return $this->host_aliases;
	}

	/**
	 * @param string $type
	 * @param array $files
	 * @param string $dest
	 * @param array $options
	 * @return array
	 * @throws Exception_Configuration
	 * @throws Exception_NotFound
	 */
	public function configuration_files(string $type, array $files, string $dest, array $options = []): array {
		$search_path = toList($options['search_path'] ?? [], ' ');
		if (empty($search_path)) {
			$search_path = $this->search_path();
			foreach ($search_path as $i => $path) {
				$search_path[$i] = path($path, $type);
			}
		}
		$search_path = array_merge($search_path, toList($options['+search_path'] ?? [], ' '));
		if (Directory::findFirst($search_path) === null) {
			if ($options['require'] ?? null) {
				throw new Exception_Configuration($type, 'Requires directory to be located in {search_path_list}', [
					'search_path' => $search_path, 'search_path_list' => implode(', ', $search_path),
				]);
			}
			$this->verboseLog("configuration_files $type not found ... skipping");
			return [];
		}
		$updates = [];
		$files = toList($files);
		foreach ($files as $mixed) {
			$source_prefix = path($type, $mixed);

			try {
				if (StringTools::ends($source_prefix, '/')) {
					$source = Directory::findFirst($search_path, $source_prefix);
				} else {
					$source = File::findFirst($search_path, $source_prefix);
				}
				$dest = path($dest, $source_prefix);
				$updates[] = [
					$source, $dest,
				];
			} catch (Exception_NotFound) {
			}
		}
		return $updates;
	}
}
