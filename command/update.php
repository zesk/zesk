<?php declare(strict_types=1);

/**
 *
 */
namespace zesk;

/**
 * Update code from remote sources in ${module}.module.conf
 *
 * @category Management
 */
class Command_Update extends Command_Base {
	protected array $option_types = [
		'share-path' => 'path',
		'source-control' => 'string',
		'dry-run' => 'boolean',
		'skip-delete' => 'boolean',
		'skip-database' => 'boolean',
		'composer-update' => 'boolean',
		'timeout' => 'integer',
		'list' => 'boolean',
		'force' => 'boolean',
		'force-check' => 'boolean',
		'*' => 'string',
	];

	protected array $option_help = [
		'share-path' => 'Copy all updated modules files to this directory instead of the default Controller_Share directories.',
		'source-control' => 'Uses source control to check in recent updates.',
		'dry-run' => 'Show what would have happened without actually doing it.',
		'skip-delete' => 'Skip DELETE_AFTER values in module configuration files.',
		'skip-database' => 'Do not store results in a local database, or load local database of last request times.',
		'composer-update' => 'Update the composer lock file explicitly. Defaults to false.',
		'timeout' => 'Timeout in milliseconds for URL downloads - this is the total time to donwload resulting packages, so keep it reasonable.',
		'list' => 'List all modules which would update',
		'force' => 'Force updates regardless if local copies match most recent downloaded copies',
		'force-check' => 'Force check for all modules regardless of when last checked.',
		'*' => 'A list of modules to download and update',
	];

	protected array $option_defaults = [
		'composer-update' => false,
		"timeout" => 120000,
	];

	protected array $load_modules = [
		"Repository",
	];

	/**
	 * Set to true in subclasses to skip Application configuration until ->go
	 *
	 * @var boolean
	 */
	public $has_configuration = false;

	/**
	 *
	 * @var array
	 */
	protected $update_db = [];

	/*
	 * @var Repository
	 */
	protected $repo = null;

	/**
	 *
	 * @var array
	 */
	private $composer_json = null;

	/**
	 *
	 * @var boolean
	 */
	private $composer_do_install = false;

	/**
	 *
	 * @var boolean
	 */
	private $composer_packages = [];

	/**
	 * Main comman entry point
	 *
	 * {@inheritdoc}
	 *
	 * @see Command::run()
	 */
	public function run() {
		$this->configure("update");

		$this->inherit_global_options();

		if ($this->help) {
			$this->usage();
			return;
		}
		if (!$this->optionBool("skip-database")) {
			// Options loaded from configuration file
			$this->verbose_log("Loading update database");
			$this->update_db = $this->update_database();
		}

		if ($this->hasOption('source-control')) {
			$vc = $this->option('source-control');
			$this->repo = Repository::factory($vc);
			if (!$this->repo) {
				$this->usage("No version-control of type \"{type}\" available", [
					"type" => $vc,
				]);
				return 1;
			} else {
				$this->verbose_log("Using repository {type}", [
					"type" => $vc,
				]);
			}
		}

		$this->app_data = [
			'application_root' => $this->application->path(),
		];
		$modules = $this->modules_to_update();

		$result = $this->before_update();
		if ($result === 0) {
			foreach ($modules as $module => $module_data) {
				if (!$this->_update_module($module, $module_data)) {
					$result = 1;
				}
			}
			$this->after_update($result);
		}

		return $result;
	}

	/**
	 * Retrieve a list of modules from the command line
	 */
	private function modules_from_command_line(array $module_options) {
		$logger = $this->application->logger;
		$modules = [];
		do {
			$module = $this->get_arg("module");
			$this->verbose_log("Updating command line module \"{module}\"", [
				"module" => $module,
			]);

			try {
				$data = $this->application->modules->load($module, $module_options);
			} catch (Exception_Directory_NotFound $e) {
				$logger->error("No such module $module found.");

				continue;
			}
			//			$data['debug'] = _dump($data);
			if (is_array($data) && array_key_exists('configuration', $data)) {
				$modules[$module] = $data;
			} else {
				$logger->warning("{name} does not have an associated configuration file {debug}", $data);
			}
		} while ($this->has_arg());
		return $modules;
	}

	/**
	 * Retrieve a list of modules from available paths
	 */
	private function modules_from_module_paths($module_options) {
		$modules = [];
		$paths = $this->application->module_path();
		if (count($paths) === 0) {
			$this->verbose_log("No module paths configured");
		}
		$sources = [];
		foreach ($paths as $path) {
			$globbed = array_merge(glob(path($path, "*/*.module.conf")), glob(path($path, "*/*/*.module.conf")), glob(path($path, "*/*.module.json")), glob(path($path, "*/*/*.module.json")));
			if (is_array($globbed)) {
				$count = count($globbed);
				$locale = $this->application->locale;
				$this->verbose_log($locale("Module path {path}: {count} {modules}"), [
					"path" => $path,
					"count" => $count,
					"modules" => $locale->plural($locale("module"), $count),
				]);
				$debug = [];
				foreach ($globbed as $glob) {
					$module = StringTools::unprefix(dirname($glob), rtrim($path, "/") . "/");
					$data = $this->application->modules->load($module, $module_options);
					//					$debug['debug'] = _dump($data);
					if (is_array($data) && array_key_exists('configuration_file', $data)) {
						$modules[$module] = $data;
						$this->verbose_log("Module {name} loaded configuration {configuration_file}", $debug + $data);
					} else {
						$this->application->logger->warning("{name} does not have an associated configuration file", $debug + $data);
					}
				}
			} else {
				$this->verbose_log("Module path {path}: no modules found", [
					"path" => $path,
				]);
			}
		}
		return $modules;
	}

	/**
	 * Determine the array of module_name => module_data to update
	 *
	 * @return array
	 */
	private function modules_to_update() {
		$module_options = [
			'load' => false,
		];

		if ($this->has_arg()) {
			$modules = $this->modules_from_command_line($module_options);
		} elseif ($this->optionBool('all')) {
			$modules = $this->modules_from_module_paths($module_options);
		} else {
			$modules = $this->application->modules->load();
		}
		if (count($modules) === 0) {
			$this->error("No modules found to update");
			return [];
		}
		$locale = $this->application->locale;
		$this->verbose_log("Will update {count} {modules}", [
			"count" => count($modules),
			"modules" => $locale->plural($locale("module"), count($modules)),
		]);
		return $modules;
	}

	/**
	 *
	 * @return integer
	 */
	private function before_update() {
		$result = $this->composer_before_update();
		return $result;
	}

	/**
	 *
	 * @param integer $result
	 */
	private function after_update($result): void {
		if ($result !== 0) {
			return;
		}
		$this->composer_after_update();
	}

	/**
	 *
	 * @return integer
	 */
	private function composer_before_update() {
		$this->composer_json = [];
		$this->composer_packages = [];

		// TODO Is this a bad idea to depend on the structure of composer.lock?
		$composer_lock = $this->application->path("composer.json");
		if (!file_exists($composer_lock)) {
			return 0;
		}

		try {
			$this->composer_json = JSON::decode(file_get_contents($composer_lock));
		} catch (Exception_Parse $e) {
			$this->error("Unable to parse JSON in {file}", [
				"file" => $composer_lock,
			]);
			return 1;
		}
		$this->composer_packages = avalue($this->composer_json, "require", []) + avalue($this->composer_json, "require-dev", []);
		return 0;
	}

	/**
	 */
	private function composer_after_update(): void {
		try {
			$composer_command = $this->optionBool("composer_update") ? "update" : "install";
			$composer = $this->composer_command();
			$devargs = $this->application->development() ? "" : " --no-dev";
			$quietargs = $this->optionBool("quiet") ? " -q" : "";
			$this->application->process->execute("$composer$quietargs $composer_command$devargs");
		} catch (Exception_Command $e) {
			$this->error($e);
		}
	}

	/**
	 *
	 * @param string $dependency
	 * @return boolean
	 */
	private function composer_has_installed($dependency) {
		[$package, $version] = pairr($dependency, ":", $dependency, null);
		return array_key_exists($package, $this->composer_packages);
	}

	/**
	 *
	 * @param array $set
	 * @return mixed
	 */
	private function update_database(array $set = null) {
		$path = $this->application->path(".update.json");
		if ($set === null) {
			if (file_exists($path)) {
				try {
					return JSON::decode(file_get_contents($path));
				} catch (\Exception $e) {
				}
			}
			return [];
		} else {
			file_put_contents($path, json_encode($set, JSON_PRETTY_PRINT));
		}
	}

	private function _run_module_hook($module, $hook_name): void {
		$logger = $this->application->logger;

		try {
			$module_object = $this->application->modules->object($module);
			if ($module_object instanceof Module) {
				$logger->debug("Running {class}::hook_{name}", [
					"class" => get_class($module_object),
					"name" => $hook_name,
				]);
				$module_object->call_hook($hook_name);
			}
		} catch (\ReflectionException $e) {
			$logger->debug("Module object for $module was not found ... skipping");
		} catch (Exception_NotFound $e) {
			$logger->debug("Module object for $module was not found ... skipping");
		} catch (Exception_Class_NotFound $e) {
			$logger->debug("Module object for $module was not found ... skipping");
		}
	}

	/**
	 * Update a single module
	 *
	 * @param string $module
	 * @param array $module_data
	 * @return array
	 */
	private function _update_module($module, array $module_data) {
		$logger = $this->application->logger;
		$force = $this->optionBool('force');
		$force_check = $this->optionBool('force-check');
		$now = time();
		$data = avalue($module_data, 'configuration');
		if (!is_array($data)) {
			$logger->debug("Module {name} does not have configuration information: {configuration_file}", $module_data + [
				'configuration_file' => '-not specified-',
			]);
			return true;
		}
		if (!ArrayTools::has_any($data, 'url;urls;versions;composer')) {
			return true;
		}
		if ($this->optionBool('list')) {
			$this->log($module);
			return true;
		}
		$this->log("Updating $module");
		$edits = [];
		$composer_updates = false;
		if (ArrayTools::has($data, "composer")) {
			$composer_updates = $this->composer_update($data);
		}
		$locale = $this->application->locale;
		$state_data = avalue($this->update_db, $module, []);
		if (!$force) {
			$checked = avalue($state_data, 'checked', null);
			$checked_time = strtotime($checked);
			$interval = $this->optionInt('check_interval', 24 * 60 * 60);
			if ($checked_time > $now - $interval) {
				$this->verbose_log("$module checked less than " . $locale->duration_string($interval, "hour") . " ago" . ($force_check ? "- checking anyway" : ""));
				if (!$force_check) {
					$this->_run_module_hook($module, "update");
					return true;
				}
			} else {
				// echo "$checked_time > $now - $interval (" . ($now - $interval) . ")\n";
			}
		}
		$edits = $this->fetch($this->app_data + $state_data + $module_data + $data);
		$did_updates = $composer_updates || (is_array($edits) && count($edits) > 0) ? true : false;
		$this->_run_module_hook($module, $did_updates ? "updated" : "update");
		if ($did_updates) {
			$this->log("{name} updated to latest version.", [
				"name" => $module,
			]);
		} else {
			$this->log("{name} is up to date.", [
				"name" => $module,
			]);
		}
		if (!$this->optionBool("skip-database")) {
			$date = gmdate('Y-m-d H:i:s');
			if ($edits === null) {
				$this->verbose_log("$module uptodate\n");
				$edits = [];
				$edits['checked'] = $date;
			} elseif ($edits instanceof Exception) {
				$message = $edits->getMessage();
				$edits = [];
				$edits['failed_message'] = $message;
				$edits['failed'] = $date;
				$this->verbose_log("$module failed: $message\n");
			} else {
				$edits = is_array($edits) ? $edits : [];
				$edits['checked'] = gmdate('Y-m-d H:i:s');
				$this->verbose_log("$module updated\n");
			}
			$this->update_db[$module] = $edits;
			$this->update_database($this->update_db);
		}
		return $did_updates;
	}

	/**
	 * Using various options and
	 *
	 * 2017-10 Added ability to use composer binary from system path -KMD
	 *
	 * @throws Exception_Configuration
	 * @return mixed|string|array|string|\zesk\NULL
	 */
	private function composer_command() {
		$paths = $this->application->paths;
		if ($this->hasOption("composer_command")) {
			return $this->option("composer_command");
		}
		$composer_phar = null;
		$composer_phar = $this->hasOption("composer_phar", true) ? $this->option("composer_phar") : $paths->which("composer.phar");
		if ($composer_phar) {
			return $this->option("php_command", "/usr/bin/env php $composer_phar");
		}
		$composer_bin = $this->hasOption("composer_bin", true) ? $this->option("composer_bin") : $paths->which("composer");
		if ($composer_bin) {
			return $composer_bin;
		}

		throw new Exception_Configuration(__CLASS__ . "::composer_phar", "Need to set composer_command, composer_phar, or composer_bin for {class}, or place composer.phar, composer into path", [
			"class" => get_class($this),
		]);
	}

	/**
	 * Update composer data as part of a module
	 *
	 * @param array $data
	 */
	private function composer_update(array $data) {
		$name = $composer = null;
		extract($data, EXTR_IF_EXISTS);

		$application = $this->application;
		$logger = $application->logger;
		$configuration = $this->application->configuration;
		if (!is_array($composer)) {
			$logger->error("Composer value is not an array: {composer}\ndata: {data}", compact("composer", "data"));
			return;
		}
		$composer_command = $this->composer_command();
		if (!ArrayTools::has_any($composer, "require;require-dev")) {
			return true;
		}
		$composer_version = to_array($application->modules->configuration($name, "composer_version"));
		$composer_require = to_list(avalue($composer, 'require', null));
		$composer_require_dev = to_list(avalue($composer, 'require-dev', null));
		$pwd = getcwd();
		chdir($application->path());
		$do_updates = $this->optionBool("composer-update");

		$changed = false;
		foreach ([
			"" => $composer_require,
			"--dev " => $composer_require_dev,
		] as $arg => $requires) {
			foreach ($requires as $require) {
				if (!is_string($require)) {
					$logger->error("Module {name} {conf_path} composer.require is not a string? {type}", [
						"type" => type($require),
					] + $data);

					continue;
				}
				[$component, $version] = pair($require, ":", $require, null);
				if (array_key_exists($component, $composer_version)) {
					$require = $component . ":" . $composer_version[$component];
				}
				if ($this->composer_has_installed($component) && !$do_updates) {
					if ($this->optionBool("dry-run")) {
						$this->log("No update for composer {require} - already installed", [
							"require" => $require,
						]);
					}
					$this->composer_do_install = true;

					continue;
				}
				if ($this->optionBool("dry-run")) {
					$this->log("Would run command: $composer_command require {require}", [
						"require" => $require,
					]);
				} else {
					$this->exec("$composer_command require -q $arg{require} 2>&1", [
						"require" => $require,
					]);
					$changed = true;
				}
			}
		}
		return true;
	}

	/**
	 *
	 * @param string $url
	 * @throws Exception_NotFound
	 * @throws Exception_System
	 * @return string[]
	 */
	private function _fetch_url($url) {
		$client = new Net_HTTP_Client($this->application, $url);
		$minutes = 5; // 2 minutes total for client to run
		$client->timeout($minutes * 60000);
		$temp_file_name = File::temporary($this->application->paths->temporary());
		$client->follow_location(true);
		$client->user_agent('Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:12.0) Gecko/20100101 Firefox/12.0');
		$client->destination($temp_file_name);
		$this->verbose_log("Downloading $url ... ");
		$client->go();
		$response_code = $client->response_code_type();
		if ($response_code === 2) {
			$filename = $client->filename();
			return [
				$temp_file_name,
				$filename,
			];
		}
		if ($response_code === 4) {
			throw new Exception_NotFound("URL {url} no longer exists", compact("url"));
		}

		throw new Exception_System("Server {url} temporarily down or returning an error? {response_code}", compact("url", "response_code"));
	}

	/**
	 *
	 * @param array $data
	 * @throws Exception_Semantics
	 * @return array
	 */
	private function urls_to_fetch(array &$data) {
		$path = $name = $versions = $url = $urls = $strip_components = $hashes = $source = $destination = $delete_after = null;
		extract(array_change_key_case($data), EXTR_IF_EXISTS);
		if ($versions !== null) {
			$version = $this->application->modules->configuration($name, "version", null);
			if (!$version) {
				$version = last(array_keys($versions));
			}
			$this->log("Updating {name} to version {version}", [
				"name" => $name,
				"version" => $version,
			]);
			$data['version'] = $version;
			extract(array_change_key_case($versions[$version]), EXTR_IF_EXISTS);
		}
		if ($urls !== null) {
			if (is_string($urls)) {
				$urls = explode(" ", $urls);
			} elseif (!is_array($urls)) {
				throw new Exception_Semantics("URLS should be a list of urls or an array");
			}
		} elseif ($url !== null) {
			$urls = [
				$url,
			];
		} else {
			return [];
		}
		$urls = map($urls, $data);
		$load_urls = [];
		foreach ($urls as $url => $value) {
			if (URL::valid($url)) {
				if (is_string($value)) {
					$load_urls[$url] = [
						'destination' => $value,
						'strip_components' => $strip_components,
					];
				} elseif (is_array($value)) {
					$load_urls[$url] = array_change_key_case($value) + [
						'destination' => $destination,
						'strip_components' => $strip_components,
					];
				}
			} elseif (!URL::valid($value)) {
				$this->error("{value} in  module {name} is not a valid URL", [
					'value' => $value,
					'name' => $name,
				]);
			} else {
				$load_urls[$value] = [
					'destination' => $destination,
					'strip_components' => $strip_components,
				];
			}
		}
		return $load_urls;
	}

	/**
	 *
	 * @param array $data
	 */
	private function fetch(array $data): self {
		// $source = $url = $destination = $strip_components = $description = $hashes = null;
		$name = $hashes = $delete_after = null;
		extract(array_change_key_case($data), EXTR_IF_EXISTS);
		if (!is_array($hashes)) {
			$hashes = [];
		}
		$load_urls = $this->urls_to_fetch($data);
		if (count($load_urls) === 0) {
			$this->debug_log("No url, urls, or versions in module {name}", $data);
			return null;
		}
		$dry_run = $this->optionBool('dry-run');
		$new_hashes = [];

		$did_updates = false;
		foreach ($load_urls as $url => $settings) {
			$destination = avalue($settings, 'destination', null);

			if ($destination === null) {
				$this->error("Need to supply a destination for $url");

				continue;
			}
			$destination = $this->compute_destination($data, $destination);
			if ($dry_run) {
				$this->log("Would download $url to $destination");

				continue;
			}

			try {
				[$temp_file_name, $filename] = $this->_fetch_url($url);
			} catch (Exception $e) {
				$this->error("Updating {url} failed: {message}", [
					"url" => $url,
					"message" => $e->getMessage(),
				]);
				return $e;
			}

			$do_update = false;
			$new_hash = md5_file($temp_file_name);
			$dest_file = path($destination, $filename);
			if ($this->optionBool('force')) {
				$do_update = true;
				$this->verbose_log('Updating forced');
			} elseif (!$this->is_unpack($filename) && !file_exists($dest_file)) {
				$do_update = true;
				$this->verbose_log("Destination file {dest_file} doesn't exist? (filename is {filename})", [
					"dest_file" => $dest_file,
					"filename" => $filename,
				]);
			} else {
				$hash = avalue($hashes, $url);
				if ($hash !== $new_hash) {
					$do_update = true;
					$this->verbose_log("Hashes don't match for {dest_file}: {hash} !== {new_hash}", [
						"dest_file" => $dest_file,
						"hash" => $hash,
						"new_hash" => $new_hash,
					]);
				} elseif (!is_dir($destination) || Directory::is_empty($destination)) {
					$do_update = true;
					$this->verbose_log("Destination directory {destination} doesn't exist", [
						"destination" => $destination,
					]);
				}
			}
			if (!$do_update) {
				@unlink($temp_file_name);

				continue;
			} else {
				$did_updates = true;
			}
			Directory::depend($destination, 0o775);

			$data['filename'] = $filename;
			$data['temp_file_name'] = $temp_file_name;
			$settings['destination'] = $destination;

			if ($this->optionBool('debug')) {
				//echo Text::format_array($data['configuration']);
			}

			$unpack_result = $this->unpack($settings + $data);

			$this->update_share($settings + $data);

			@unlink($temp_file_name);
			if ($unpack_result) {
				$new_hashes[$url] = $new_hash;
			}
		}
		if (!$this->optionBool('skip-delete') && is_array($delete_after)) {
			$this->_delete_files($destination, $delete_after);
		}
		if (count($new_hashes) > 0) {
			return [
				'hashes' => $new_hashes + $hashes,
			];
		}
		$this->verbose_log("$name unchanged");
		return null;
	}

	private function _delete_files($destination, array $files): void {
		$delete_files = [];
		foreach ($files as $file) {
			if (str_contains($file, "*")) {
				$path = path($destination, $file);
				$paths = glob($path);
				if (count($paths) === 0) {
					$this->verbose_log("Wildcard delete_after matched NO files $path");
				} else {
					$delete_files = array_merge($delete_files, $paths);
				}
			} else {
				$path = path($destination, $file);
				$delete_file = realpath($path);
				if (!$delete_file) {
					$this->verbose_log("delete_after file $delete_file not found");
				} else {
					$delete_files[] = $delete_file;
				}
			}
		}
		$delete_files = array_unique($delete_files);
		foreach ($delete_files as $index => $delete) {
			if (!begins($delete, $destination)) {
				$this->verbose_log("Deleted file {delete} does not contain prefix {destination} - skipping", [
					"delete" => $delete,
					"destination" => $destination,
				]);
				unset($delete_files[$index]);
			}
		}
		foreach ($delete_files as $delete) {
			if (is_dir($delete)) {
				$this->log("Deleting directory {delete}", [
					"delete" => $delete,
				]);
				Directory::delete($delete);
			} elseif (is_file($delete)) {
				$this->log("Deleting file {delete}", [
					"delete" => $delete,
				]);
				unlink($delete);
			} else {
				$this->debug_log("No delete file found; $delete");
			}
		}
	}

	private function _which_command($cmd) {
		$path = $this->application->paths->which($cmd);
		if ($path) {
			return $path;
		}
		$args = [
			"command" => $cmd,
			"path" => implode(":", $this->application->command_path()),
		];

		throw new Exception_File_NotFound($cmd, map($this->application->theme('error/update-command-not-found'), $args));
	}

	private function is_unpack($filename) {
		if (StringTools::ends($filename, [
			".tar.gz",
			".tgz",
			".tar",
		])) {
			return true;
		}
		if (StringTools::ends($filename, [
			".zip",
		])) {
			return true;
		}
		return false;
	}

	private function unpack(array $data) {
		$filename = $temp_file_name = $destination = null;
		$this->debug_log("Unpacking {filename}", [
			"filename" => $filename,
		]);
		extract($data, EXTR_IF_EXISTS);
		if (StringTools::ends($filename, [
			".tar.gz",
			".tgz",
			".tar",
		])) {
			$this->debug_log("Unpacking tar file {filename}", [
				"filename" => $filename,
			]);
			$result = self::unpack_tar($data);
		} elseif (StringTools::ends($filename, [
			".zip",
		])) {
			$this->debug_log("Unpacking ZIP file {filename}", [
				"filename" => $filename,
			]);
			$result = self::unpack_zip($data);
		} else {
			$full_destination = path($destination, $filename);
			$this->debug_log("Copying directory {temp_file_name} => {full_destination}", [
				"temp_file_name" => $filename,
				'full_destination' => $full_destination,
			]);
			if (is_dir($temp_file_name)) {
				$result = Directory::copy($temp_file_name, $full_destination, true);
			} else {
				$result = copy($temp_file_name, $full_destination);
			}
		}
		if (!$result) {
			return $result;
		}
		// Clean up perms
		foreach (Directory::list_recursive($destination) as $f) {
			$path = path($destination, $f);
			chmod($path, is_file($path) ? 0o644 : 0o755);
		}
		return $result;
	}

	/**
	 *
	 * @param array $data
	 * @throws Exception
	 * @return boolean
	 */
	private function update_share(array $data) {
		$source = $this->share_source($data);
		$dest = $this->share_destination($data);
		if (!$source || !$dest) {
			return false;
		}
		if (!$this->need_update($dest)) {
			return false;
		}

		try {
			$this->debug_log("Copying share directory from {source} to {dest} for module {name}", [
				"source" => $source,
				"dest" => $dest,
				"name" => $data['name'],
			]);
			Directory::copy($source, $dest, true);
		} catch (Exception $e) {
			$this->debug_log("failed because of {e} ... rolling back", [
				"e" => $e,
			]);
			$this->rollback($data);

			throw $e;
		}
		$this->post_update($data);
		return true;
	}

	/**
	 * Called recursively to trim down an archive and remove unwanted cruft.
	 * tar supports this on some systems, but we've extended the meaning to allow simple matching of
	 * paths within the
	 * archive.
	 * Some authors generate
	 *
	 * @param string $temp_directory_name
	 *        	Path of temporary path for work
	 * @param string $final_destination
	 *        	The final destination path
	 * @param mixed $strip_components
	 *        	Number of diretories to strip, or filename patterns to match/remove
	 * @throws Exception_File_Permission
	 * @return boolean
	 */
	private function strip_components($temp_directory_name, $final_destination, $strip_components) {
		assert("is_dir('$temp_directory_name')");
		assert("is_dir('$final_destination')");

		$match = null;
		if (is_numeric($strip_components)) {
			$match = null;
			$n_components = intval($strip_components);
			$strip_components = $n_components - 1;
		} else {
			if (empty($strip_components)) {
				$n_components = 0;
			} else {
				$parts = explode("/", $strip_components);
				$n_components = count($parts);
				$match = array_shift($parts);
				$match = $match === '*' ? "/.*/" : '/' . preg_quote($match, '/') . '/';
				$strip_components = implode("/", $parts);
			}
		}
		assert($n_components >= 0);

		if ($n_components > 0) {
			foreach (Directory::ls($temp_directory_name) as $d) {
				$dir = path($temp_directory_name, $d);
				if (is_dir($dir)) {
					if ($match !== null) {
						// echo "preg_match($match, $d) === " . json_encode(preg_match($match, $d)) . "\n";
					}
					if ($match === null || preg_match($match, $d)) {
						self::strip_components($dir, $final_destination, $strip_components);
					}
				}
			}
		} else {
			$logger = $this->application->logger;
			$debug = $this->debug;
			if ($debug) {
				$logger->debug("strip_components: level=0 Copying $temp_directory_name");
			}
			foreach (Directory::ls($temp_directory_name) as $f) {
				$source_path = path($temp_directory_name, $f);
				$dest_path = path($final_destination, $f);
				if ($debug) {
					$logger->debug("strip_components: Copying $source_path to $dest_path");
				}
				if (is_file($source_path)) {
					if (!copy($source_path, $dest_path)) {
						throw new Exception_File_Permission($dest_path, "rename $source_path to $dest_path");
					}
				} else {
					if (!Directory::copy($source_path, $dest_path, true)) {
						Directory::delete($dest_path);

						throw new Exception_File_Permission($dest_path, "Directory::copy $source_path to $dest_path");
					}
				}
			}
		}
		return true;
	}

	private function share_destination(array $data) {
		if (!$this->hasOption('share-path')) {
			return null;
		}
		$name = $data['name'];
		return path($this->option('share-path'), $name);
	}

	private function share_source(array $data) {
		if (!$this->hasOption('share_path')) {
			return null;
		}
		$path = $data['path'];
		$share_path = avalue($data, 'share_path');
		if ($share_path && is_dir($share_path)) {
			return $share_path;
		}
		$share_path = path($path, 'share');
		if ($share_path && is_dir($share_path)) {
			return $share_path;
		}
		return null;
	}

	private function compute_destination(array $data, $destination) {
		$application_root = $data['application_root'];
		$path = $data['path'];
		$name = $data['name'];
		if (begins($destination, $path)) {
			$this->application->logger->error("Module {name} uses module path for updates - deprecated! Use application_root instead.", compact("name"));
			$destination = StringTools::unprefix($destination, $path);
		}
		if (begins($destination, $application_root)) {
			$destination = StringTools::unprefix($destination, $application_root);
		}
		/* Disable share-path for now */
		// 		if (trim($destination, '/') === 'share' && $this->hasOption('share-path')) {
		// 			return path($this->option('share-path'), $name);
		// 		}
		return path($application_root, $destination);
	}

	/**
	 * Many systems do not support `tar --strip-components`, so default to internal method of
	 * handling
	 *
	 * @param array $data
	 * @return boolean
	 */
	private function unpack_tar(array $data) {
		$filename = $temp_file_name = $destination = $strip_components = $name = null;
		extract($data, EXTR_IF_EXISTS);
		$args = [];
		$args[] = $this->_which_command('tar');
		$args[] = StringTools::ends($filename, "gz") ? "zxf" : "xf";
		$args[] = $temp_file_name;
		$actual_destination = $destination;
		if ($strip_components) {
			$destination = Directory::depend($this->application->paths->temporary($name . '-' . $this->application->process->id()));
		}
		$args[] = "-C '$destination'";
		return $this->_unpack($args, $destination, $actual_destination, $strip_components);
	}

	/**
	 * Unpack a downloaded ZIP file
	 *
	 * @param array $data
	 * @return boolean
	 */
	private function unpack_zip(array $data) {
		$filename = $temp_file_name = $destination = $strip_components = $name = null;
		extract($data, EXTR_IF_EXISTS);
		$args = [];
		$args[] = $this->_which_command('unzip');
		$args[] = '-o';
		$args[] = $temp_file_name;
		$actual_destination = $destination;
		if ($strip_components) {
			$destination = Directory::depend($this->application->paths->temporary($name . '-' . $this->application->process->id()));
		}
		$args[] = "-d '$destination'";
		return $this->_unpack($args, $destination, $actual_destination, $strip_components);
	}

	/**
	 * Unpack generic
	 *
	 * @param array $data
	 * @return boolean
	 */
	private function _unpack(array $args, $destination, $actual_destination, $strip_components) {
		$command = implode(" ", $args);
		$return_var = null;
		ob_start();
		exec($command, $output, $return_var);
		$output = ob_end_clean();
		if ($return_var !== 0) {
			if ($strip_components) {
				Directory::delete($destination);
			}
			$this->error("$command failed:\n$output");
			return false;
		}
		if ($strip_components) {
			$this->debug_log("Stripping components {strip_components}", [
				"strip_components" => $strip_components,
			]);
			return $this->strip_components($destination, $actual_destination, $strip_components);
		}
		return true;
	}

	private function need_update($destination) {
		if (!$this->repo) {
			return true;
		}
		return $this->repo->need_update($destination);
	}

	private function rollback($destination) {
		if (!$this->repo) {
			return true;
		}
		return $this->repo->rollback($destination);
	}
}
