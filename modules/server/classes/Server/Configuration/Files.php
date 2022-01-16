<?php declare(strict_types=1);

/**
 *
 * @version $URL: https://code.marketacumen.com/zesk/trunk/modules/server/classes/Server/Configuration/Files.php $
 * @package zesk
 * @subpackage server
 * @author $Author: kent $
 * @copyright Copyright &copy; 2011, Market Acumen, Inc.
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Server_Configuration_Files extends Server_Configuration {
	protected $host_path = null;

	protected $host_aliases = [];

	protected $variables = [];

	public function __construct(Server_Platform $platform, $options = null) {
		parent::__construct($platform, $options);

		$this->_configure_host_path();
		$this->_load_aliases();
		$this->_load_configuration();
	}

	private function _configure_host_path(): void {
		$this->host_path = $this->option('host_path');
		if ($this->host_path) {
			$this->verbose_log("host_path is {host_path}", [
				"host_path" => $this->host_path,
			]);
			if (!is_dir($this->host_path)) {
				throw new Exception_Directory_NotFound($this->host_path);
			}
		} else {
			throw new Exception_Configuration("host_path", "Must be set in Server_Configuration_Files");
		}
	}

	private function _load_aliases(): void {
		$this->host_aliases = [];

		try {
			$alias_file = File::lines(path($this->host_path, "aliases"));
			$this->host_aliases = ArrayTools::trim_clean(ArrayTools::ktrim(ArrayTools::kpair($alias_file), " \t"));
		} catch (Exception_File_NotFound $e) {
		}
	}

	private function _load_configuration(): void {
		$app = $this->application;
		$hostname = $this->platform->hostname();
		$alias_hostname = avalue($this->host_aliases(), $hostname, $hostname);
		if ($alias_hostname !== $hostname) {
			$app->logger->warning("Server_Configuration_Files _load_configuration: {hostname} aliaesed to {alias_hostname}", [
				"hostname" => $hostname,
				"alias_hostname" => $alias_hostname,
			]);
		}
		$searched_paths = [];
		$this->search_path("all $alias_hostname");
		$files = $this->option_list('files', $app->configuration->get('server_configuration_file', 'environment.sh'));

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
						$app->logger->debug("No configuration file {conf}", [
							"conf" => $conf,
						]);

						continue;
					}
					$variables = $this->platform->conf_load($conf, [
						'variables' => $this->options,
						'overwrite' => true,
					]);
					$result = $variables + $result;
					$this->setOption($variables);
					$app->logger->debug("Loading configuration $conf");
				}
				$searched_paths[$path] = true;
			}
			$search_path = $this->search_path();
		}
		$app->logger->debug("configuration search path {paths}, files {files}, result {result}", [
			"paths" => implode(",", $search_path),
			"files" => implode(",", $files),
			"result" => PHP::dump($result),
		]);
	}

	public function feature_list() {
		return ArrayTools::trim_clean($this->option_list("FEATURES", $this->option_list("SERVICES", [], " "), " "));
	}

	public function search_path($set = null) {
		if ($set !== null) {
			$this->setOption('SEARCH_PATH', $set);
			return $this;
		}
		return $this->option_list("SEARCH_PATH", [], " ");
	}

	public function remote_package($url): void {
	}

	public function configure_feature(Server_Feature $feature): void {
		$shortname = $feature->code;
		$files = [
			path($feature->configure_path(), 'feature.conf'),
		];
		$files = array_merge($files, File::find_all($this->search_path(), path($shortname, "feature.conf")));
		$settings = [];
		foreach ($files as $file) {
			if (is_file($file)) {
				$settings = $this->platform->conf_load($file) + $settings;
			}
		}
		$this->application->logger->debug("Configured feature {class} {settings}", [
			"class" => get_class($feature),
			"settings" => Text::format_pairs($settings),
		]);
		$feature->setOption($settings);
	}

	final public function host_aliases() {
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
			[$line] = pair(trim($line), "#", $line);
			$line = trim($line);
			if (empty($line)) {
				continue;
			}
			$name = $alias = null;
			[$name, $alias] = pair($line, " ", $line);
			if ($alias === null) {
				continue;
			}
			$name = trim($name);
			$alias = trim($alias);
			if (array_key_exists($name, $this->host_aliases)) {
				$this->verbose_log("Alias file $alias_file defines $name twice");
			}
			$this->host_aliases[$name] = $alias;
		}
		return $this->host_aliases;
	}

	public function configuration_files($type, $files, $dest, array $options = []) {
		$search_path = to_list(avalue($options, 'search_path'), [], " ");
		if (empty($search_path)) {
			$search_path = $this->search_path();
			foreach ($search_path as $i => $path) {
				$search_path[$i] = path($path, $type);
			}
		}
		$add_search_path = to_list(avalue($options, "+search_path"), [], " ");
		if (is_array($add_search_path)) {
			$search_path = array_merge($search_path, $add_search_path);
		}
		if (Directory::find_first($search_path) === null) {
			if (avalue($options, "require")) {
				throw new Exception_Configuration($type, "Requires directory to be located in {search_path_list}", [
					"search_path" => $search_path,
					"search_path_list" => implode(", ", $search_path),
				]);
			}
			$this->verbose_log("configuration_files $type not found ... skipping");
			return [];
		}
		$updates = [];
		$files = to_list($files);
		foreach ($files as $mixed) {
			$source_prefix = path($type, $mixed);
			if (StringTools::ends($source_prefix, "/")) {
				$source = Directory::find_first($search_path, $source_prefix);
			} else {
				$source = File::find_first($search_path, $source_prefix);
			}
			if ($source) {
				$dest = path($dest, $source_prefix);
				$updates[] = [
					$source,
					$dest,
				];
			}
		}
		return $updates;
	}
}
