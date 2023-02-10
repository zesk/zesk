<?php
declare(strict_types=1);

/**
 *
 */

namespace zesk;

use zesk\Repository\Base as Repository;

/**
 * Update code from remote sources in ${module}.module.conf
 *
 * @category Management
 */
class Command_Update extends Command_Base {
	protected array $app_data;

	protected array $option_types = [
		'share-path' => 'path', 'source-control' => 'string', 'dry-run' => 'boolean', 'skip-delete' => 'boolean',
		'skip-database' => 'boolean', 'composer-update' => 'boolean', 'timeout' => 'integer', 'list' => 'boolean',
		'force' => 'boolean', 'force-check' => 'boolean', '*' => 'string',
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
		'composer-update' => false, 'timeout' => 120000,
	];

	protected array $load_modules = [
		'Repository',
	];

	/**
	 * Set to true in subclasses to skip Application configuration until ->go
	 *
	 * @var boolean
	 */
	public bool $has_configuration = false;

	/**
	 *
	 * @var array
	 */
	protected array $update_db = [];

	/*
	 * @var Repository
	 */
	protected ?Repository $repo = null;

	/**
	 *
	 * @var array
	 */
	private array $composer_json = [];

	/**
	 *
	 * @var boolean
	 */
	private bool $composer_do_install = false;

	/**
	 *
	 * @var array
	 */
	private array $composer_packages = [];

	/**
	 * @return int|bool
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Parameter
	 */
	public function run(): int {
		$this->configure('update');

		$this->inheritConfiguration();

		if ($this->help) {
			$this->usage();
			return 1;
		}
		if (!$this->optionBool('skip-database')) {
			// Options loaded from configuration file
			$this->verboseLog('Loading update database');
			$this->update_db = $this->updateDatabase();
		}

		if ($this->hasOption('source-control')) {
			$vc = $this->option('source-control');
			$this->repo = Repository::factory($vc);
			if (!$this->repo) {
				$this->usage('No version-control of type "{type}" available', [
					'type' => $vc,
				]);
				return 1;
			} else {
				$this->verboseLog('Using repository {type}', [
					'type' => $vc,
				]);
			}
		}

		$this->app_data = [
			'application_root' => $this->application->path(),
		];
		$modules = $this->modules_to_update();

		$result = $this->beforeUpdate();
		if ($result === 0) {
			foreach ($modules as $module => $moduleObject) {
				if (!$this->_updateModule($moduleObject)) {
					$result = 1;
				}
			}
			$this->afterUpdate($result);
		}

		return $result;
	}

	/**
	 * Retrieve a list of modules from the command line
	 */
	private function modules_from_command_line(): array {
		$modules = [];
		do {
			$module = $this->getArgument('module');
			$this->verboseLog('Updating command line module "{module}"', [
				'module' => $module,
			]);
			if ($this->application->modules->exists($module)) {
				$modules[] = $module;
			} else {
				$this->application->logger->error('No such module {module} found', ['module' => $module]);
			}
		} while ($this->hasArgument());
		return $modules;
	}

	/**
	 * Retrieve a list of modules from available paths
	 */
	private function modules_from_module_paths(): array {
		$modules = [];
		$paths = $this->application->modulePath();
		if (count($paths) === 0) {
			$this->verboseLog('No module paths configured');
		}
		foreach ($paths as $path) {
			$globbed = array_merge(glob(path($path, '*/*.module.conf')), glob(path($path, '*/*/*.module.conf')), glob(path($path, '*/*.module.json')), glob(path($path, '*/*/*.module.json')));
			if (is_array($globbed)) {
				$count = count($globbed);
				$locale = $this->application->locale;
				$this->verboseLog($locale('Module path {path}: {count} {modules}'), [
					'path' => $path, 'count' => $count, 'modules' => $locale->plural($locale('module'), $count),
				]);
				foreach ($globbed as $glob) {
					$moduleName = StringTools::removePrefix(dirname($glob), rtrim($path, '/') . 'Update.php/');
					$moduleObject = $this->application->modules->load($moduleName);
					if ($moduleObject->moduleConfigurationFile()) {
						$modules[$moduleName] = $moduleObject;
						$this->verboseLog('Module {name} loaded configuration {configurationFile}', $moduleObject->moduleData());
					} else {
						$this->application->logger->warning('{name} does not have an associated configuration file', [
							'name' => $moduleName,
						]);
					}
				}
			} else {
				$this->verboseLog('Module path {path}: no modules found', [
					'path' => $path,
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
	private function modules_to_update(): array {
		if ($this->hasArgument()) {
			$modules = $this->modules_from_command_line();
		} elseif ($this->optionBool('all')) {
			$modules = $this->modules_from_module_paths();
		} else {
			$modules = $this->application->modules->moduleNames();
		}
		if (count($modules) === 0) {
			$this->error('No modules found to update');
			return [];
		}
		$locale = $this->application->locale;
		$this->verboseLog('Will update {count} {modules}', [
			'count' => count($modules), 'modules' => $locale->plural($locale->__('module'), count($modules)),
		]);
		return $modules;
	}

	/**
	 *
	 * @return integer
	 */
	private function beforeUpdate(): int {
		return $this->composerBeforeUpdate();
	}

	/**
	 *
	 * @param int $result
	 */
	private function afterUpdate(int $result): void {
		if ($result !== 0) {
			return;
		}
		$this->composerAfterUpdate();
	}

	/**
	 *
	 * @return int
	 */
	private function composerBeforeUpdate(): int {
		$this->composer_json = [];
		$this->composer_packages = [];

		// TODO Is this a bad idea to depend on the structure of composer.lock?
		$composer_lock = $this->application->path('composer.json');
		if (!file_exists($composer_lock)) {
			return 0;
		}

		try {
			$this->composer_json = JSON::decode(file_get_contents($composer_lock));
		} catch (Exception_Parse $e) {
			$this->error('Unable to parse JSON in {file}', [
				'file' => $composer_lock,
			]);
			return 1;
		}
		$this->composer_packages = $this->composer_json['require'] ?? [] + $this->composer_json['require-dev'] ?? [];
		return 0;
	}

	/**
	 */
	private function composerAfterUpdate(): void {
		try {
			$composer_command = $this->optionBool('composer_update') ? 'update' : 'install';
			$composer = $this->composerCommand();
			$devargs = $this->application->development() ? '' : ' --no-dev';
			$quietargs = $this->optionBool('quiet') ? ' -q' : '';
			$this->application->process->execute("$composer$quietargs $composer_command$devargs");
		} catch (Exception_Configuration|Exception_Command $e) {
			$this->error($e);
		}
	}

	/**
	 *
	 * @param string $dependency
	 * @return boolean
	 */
	private function composerHasInstalled(string $dependency): bool {
		[$package] = reversePair($dependency, ':', $dependency, '');
		return array_key_exists($package, $this->composer_packages);
	}

	/**
	 * @return string
	 */
	private function _databasePath(): string {
		return $this->application->path('.update.json');
	}

	/**
	 *
	 * @param array $set
	 * @return $this
	 * @throws Exception_File_Permission
	 */
	private function updateDatabase(array $set): self {
		$path = $this->application->path('.update.json');
		File::put($path, JSON::encodePretty($set));
		return $this;
	}

	/**
	 * Load the database file
	 *
	 * @return array
	 */
	private function loadDatabase(): array {
		$path = $this->_databasePath();
		if (file_exists($path)) {
			try {
				return JSON::decode(file_get_contents($path));
			} catch (Exception_Parse $e) {
			}
		}
		return [];
	}

	private function _runModuleHook(string $module, string $hook_name): void {
		$logger = $this->application->logger;

		try {
			$module_object = $this->application->modules->object($module);
			$logger->debug('Running {class}::hook_{name}', [
				'class' => $module_object::class, 'name' => $hook_name,
			]);
			$module_object->callHook($hook_name);
		} catch (Exception_NotFound $e) {
			$logger->debug("Module object for $module was not found ... skipping");
		}
	}

	/**
	 * Update a single module
	 *
	 * @param Module $module
	 * @return bool Did updates
	 */
	private function _updateModule(Module $module): bool {
		$moduleName = $module->name();
		$logger = $this->application->logger;
		$force = $this->optionBool('force');
		$force_check = $this->optionBool('force-check');
		$now = time();
		$data = $module->moduleConfiguration();
		if (!ArrayTools::hasAnyKey($data, ['url', 'urls', 'versions', 'composer'])) {
			return true;
		}
		if ($this->optionBool('list')) {
			$this->log($module);
			return true;
		}
		$this->log("Updating $moduleName");
		$composer_updates = false;
		if (ArrayTools::has($data, 'composer')) {
			$composer_updates = $this->composerUpdate($data);
		}
		$locale = $this->application->locale;
		$state_data = $this->update_db[$moduleName] ?? [];
		if (!$force) {
			$checked = $state_data['checked'] ?? null;
			$checked_time = strtotime($checked);
			$interval = $this->optionInt('check_interval', 24 * 60 * 60);
			if ($checked_time > $now - $interval) {
				$this->verboseLog("$moduleName checked less than " . $locale->duration_string($interval, 'hour') . ' ago' . ($force_check ? '- checking anyway' : ''));
				if (!$force_check) {
					$this->_runModuleHook($moduleName, 'update');
					return true;
				}
			}
		}
		$edits = $this->fetch($this->app_data + $state_data + $module->moduleData() + $data);
		$did_updates = $composer_updates || (is_array($edits) && count($edits) > 0);
		$this->_runModuleHook($moduleName, $did_updates ? 'updated' : 'update');
		if ($did_updates) {
			$this->log('{name} updated to latest version.', [
				'name' => $moduleName,
			]);
		} else {
			$this->log('{name} is up to date.', [
				'name' => $moduleName,
			]);
		}
		if (!$this->optionBool('skip-database')) {
			$date = gmdate('Y-m-d H:i:s');
			if ($edits === null) {
				$this->verboseLog("$moduleName uptodate\n");
				$edits = [];
				$edits['checked'] = $date;
			} elseif ($edits instanceof Exception) {
				$message = $edits->getMessage();
				$edits = [];
				$edits['failed_message'] = $message;
				$edits['failed'] = $date;
				$this->verboseLog("$moduleName failed: $message\n");
			} else {
				$edits = is_array($edits) ? $edits : [];
				$edits['checked'] = gmdate('Y-m-d H:i:s');
				$this->verboseLog("$moduleName updated\n");
			}
			$this->update_db[$moduleName] = $edits;
			$this->updateDatabase($this->update_db);
		}
		return $did_updates;
	}

	/**
	 * Using various options and
	 *
	 * 2017-10 Added ability to use composer binary from system path -KMD
	 *
	 * @return string
	 * @throws Exception_Configuration
	 */
	private function composerCommand(): string {
		$paths = $this->application->paths;
		if ($this->hasOption('composer_command')) {
			return strval($this->option('composer_command'));
		}

		try {
			$composer_phar = $this->hasOption('composer_phar', true) ? strval($this->option('composer_phar')) : $paths->which('composer.phar');
			return strval($this->option('php_command', "/usr/bin/env php $composer_phar"));
		} catch (Exception_NotFound) {
		}

		try {
			return $this->hasOption('composer_bin', true) ? strval($this->option('composer_bin')) : $paths->which('composer');
		} catch (Exception_NotFound) {
		}

		throw new Exception_Configuration(__CLASS__ . '::composer_phar', 'Need to set composer_command, composer_phar, or composer_bin for {class}, or place composer.phar, composer into path', [
			'class' => get_class($this),
		]);
	}

	/**
	 * Update composer data as part of a module
	 *
	 * @param array $data
	 * @return bool
	 * @throws Exception_Command
	 * @throws Exception_Configuration
	 * @throws Exception_Syntax
	 */
	private function composerUpdate(array $data) {
		$name = $data['name'] ?? null;
		$composer = $data['composer'] ?? null;
		$application = $this->application;
		$logger = $application->logger;
		$configuration = $this->application->configuration;
		if (!is_array($composer)) {
			throw new Exception_Syntax("Composer value is not an array: {composer}\ndata: {data}", [
				'composer' => $composer, 'data' => $data,
			]);
		}
		$composer_command = $this->composerCommand();
		if (!ArrayTools::hasAnyKey($composer, ['require', 'require-dev'])) {
			return true;
		}
		$composer_version = toArray($application->modules->configuration($name)['composerVersion'] ?? []);
		$composer_require = toList($composer['require'] ?? null);
		$composer_require_dev = toList($composer['require-dev'] ?? null);
		chdir($application->path());
		$do_updates = $this->optionBool('composer-update');

		foreach ([
			'' => $composer_require, '--dev ' => $composer_require_dev,
		] as $arg => $requires) {
			foreach ($requires as $require) {
				if (!is_string($require)) {
					$logger->error('Module {name} {conf_path} composer.require is not a string? {type}', [
						'type' => type($require),
					] + $data);

					continue;
				}
				[$component] = pair($require, ':', $require, '');
				if (array_key_exists($component, $composer_version)) {
					$require = $component . ':' . $composer_version[$component];
				}
				if ($this->composerHasInstalled($component) && !$do_updates) {
					if ($this->optionBool('dry-run')) {
						$this->log('No update for composer {require} - already installed', [
							'require' => $require,
						]);
					}
					$this->composer_do_install = true;

					continue;
				}
				if ($this->optionBool('dry-run')) {
					$this->log("Would run command: $composer_command require {require}", [
						'require' => $require,
					]);
				} else {
					$this->exec("$composer_command require -q $arg{require} 2>&1", [
						'require' => $require,
					]);
				}
			}
		}
		return true;
	}

	/**
	 *
	 * @param string $url
	 * @return string[]
	 * @throws Exception_Directory_Create
	 * @throws Exception_Directory_Permission
	 * @throws Exception_DomainLookup
	 * @throws Exception_NotFound
	 * @throws Exception_Parameter
	 * @throws Exception_System
	 * @throws Exception_Unsupported
	 * @throws Net_HTTP_Client_Exception
	 */
	private function _fetchURL(string $url): array {
		$client = new Net_HTTP_Client($this->application, $url);
		$minutes = 5; // 2 minutes total for client to run
		$client->timeout($minutes * 60000);
		$temp_file_name = File::temporary($this->application->paths->temporary());
		$client->setFollowLocation(true);
		$client->userAgent('Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:12.0) Gecko/20100101 Firefox/12.0');
		$client->destination($temp_file_name);
		$this->verboseLog("Downloading $url ... ");
		$client->go();
		$response_code = $client->response_code_type();
		if ($response_code === 2) {
			$filename = $client->filename();
			return [
				$temp_file_name, $filename,
			];
		}
		if ($response_code === 4) {
			throw new Exception_NotFound('URL {url} no longer exists', ['url' => $url]);
		}

		throw new Exception_System('Server {url} temporarily down or returning an error? {response_code}', [
			'url' => $url, 'response_code' => $response_code,
		]);
	}

	/**
	 *
	 * @param array $data
	 * @return array
	 */
	private function urlsToFetch(array &$data): array {
		$name = $data['name'] ?? null;
		$versions = toArray($data['versions'] ?? null);
		$urls = toList($data['urls'] ?? null, toText($data['url'] ?? ''), ' ');
		$strip_components = toBool($data['strip_components'] ?? false);
		$destination = $data['path'] ?? null;

		if (count($versions) > 0) {
			$version = $this->application->modules->configuration($name)['version'] ?? null;
			if (!$version) {
				$version = last(array_keys($versions));
			}
			$this->log('Updating {name} to version {version}', [
				'name' => $name, 'version' => $version,
			]);
			$data['version'] = $version;
			extract(array_change_key_case($versions[$version]), EXTR_IF_EXISTS);
		}
		if (count($urls) === 0) {
			return [];
		}
		$urls = map($urls, $data);
		$load_urls = [];
		foreach ($urls as $url => $value) {
			if (URL::valid($url)) {
				if (is_string($value)) {
					$load_urls[$url] = [
						'destination' => $value, 'strip_components' => $strip_components,
					];
				} elseif (is_array($value)) {
					$load_urls[$url] = array_change_key_case($value) + [
						'destination' => $destination, 'strip_components' => $strip_components,
					];
				}
			} elseif (!URL::valid($value)) {
				$this->error('{value} in  module {name} is not a valid URL', [
					'value' => $value, 'name' => $name,
				]);
			} else {
				$load_urls[$value] = [
					'destination' => $destination, 'strip_components' => $strip_components,
				];
			}
		}
		return $load_urls;
	}

	/**
	 *
	 * @param array $data
	 * @return self|null
	 * @throws Exception
	 * @throws Exception_Directory_Create
	 * @throws Exception_Directory_NotFound
	 * @throws Exception_Directory_Permission
	 * @throws Exception_File_NotFound
	 * @throws Exception_Semantics
	 */
	private function fetch(array $data): self|null {
		$name = $data['name'] ?? null;
		$hashes = $data['hashes'] ?? null;
		$delete_after = $data['delete_after'] ?? null;

		if (!is_array($hashes)) {
			$hashes = [];
		}
		$load_urls = $this->urlsToFetch($data);
		if (count($load_urls) === 0) {
			$this->debugLog('No url, urls, or versions in module {name}', $data);
			return null;
		}
		$dry_run = $this->optionBool('dry-run');
		$new_hashes = [];

		foreach ($load_urls as $url => $settings) {
			$destination = $settings['destination'] ?? null;

			if ($destination === null) {
				$this->error("Need to supply a destination for $url");

				continue;
			}
			$destination = $this->computeDestination($data, $destination);
			if ($dry_run) {
				$this->log("Would download $url to $destination");

				continue;
			}

			try {
				[$temp_file_name, $filename] = $this->_fetchURL($url);
			} catch (Exception $e) {
				$this->error('Updating {url} failed: {message}', [
					'url' => $url, 'message' => $e->getMessage(),
				]);
				return $e;
			}

			$do_update = false;
			$new_hash = md5_file($temp_file_name);
			$dest_file = path($destination, $filename);
			if ($this->optionBool('force')) {
				$do_update = true;
				$this->verboseLog('Updating forced');
			} elseif (!$this->isUnpack($filename) && !file_exists($dest_file)) {
				$do_update = true;
				$this->verboseLog('Destination file {dest_file} doesn\'t exist? (filename is {filename})', [
					'dest_file' => $dest_file, 'filename' => $filename,
				]);
			} else {
				$hash = $hashes[$url] ?? null;
				if ($hash !== $new_hash) {
					$do_update = true;
					$this->verboseLog('Hashes don\'t match for {dest_file}: {hash} !== {new_hash}', [
						'dest_file' => $dest_file, 'hash' => $hash, 'new_hash' => $new_hash,
					]);
				} elseif (!is_dir($destination) || Directory::isEmpty($destination)) {
					$do_update = true;
					$this->verboseLog('Destination directory {destination} does not exist', [
						'destination' => $destination,
					]);
				}
			}
			if (!$do_update) {
				unlink($temp_file_name);

				continue;
			} else {
				$did_updates = true;
			}
			Directory::depend($destination, 0o775);

			$data['filename'] = $filename;
			$data['temp_file_name'] = $temp_file_name;
			$settings['destination'] = $destination;

			$unpack_result = $this->unpack($settings + $data);

			$this->updateShare($settings + $data);

			unlink($temp_file_name);
			if ($unpack_result) {
				$new_hashes[$url] = $new_hash;
			}
		}
		if (!$this->optionBool('skip-delete') && is_array($delete_after)) {
			$this->_deleteFiles($destination, $delete_after);
		}
		if (count($new_hashes) > 0) {
			return [
				'hashes' => $new_hashes + $hashes,
			];
		}
		$this->verboseLog("$name unchanged");
		return null;
	}

	private function _deleteFiles(string $destination, array $files): void {
		$filesToDelete = [];
		foreach ($files as $file) {
			if (str_contains($file, '*')) {
				$path = path($destination, $file);
				$paths = glob($path);
				if (count($paths) === 0) {
					$this->verboseLog("Wildcard delete_after matched NO files $path");
				} else {
					$filesToDelete = array_merge($filesToDelete, $paths);
				}
			} else {
				$path = path($destination, $file);
				$delete_file = realpath($path);
				if (!$delete_file) {
					$this->verboseLog("delete_after file $delete_file not found");
				} else {
					$filesToDelete[] = $delete_file;
				}
			}
		}
		$filesToDelete = array_unique($filesToDelete);
		foreach ($filesToDelete as $index => $delete) {
			if (!str_starts_with($delete, $destination)) {
				$this->verboseLog('Deleted file {delete} does not contain prefix {destination} - skipping', [
					'delete' => $delete, 'destination' => $destination,
				]);
				unset($filesToDelete[$index]);
			}
		}
		foreach ($filesToDelete as $delete) {
			if (is_dir($delete)) {
				$this->log('Deleting directory {delete}', [
					'delete' => $delete,
				]);
				Directory::delete($delete);
			} elseif (is_file($delete)) {
				$this->log('Deleting file {delete}', [
					'delete' => $delete,
				]);
				unlink($delete);
			} else {
				$this->debugLog("No delete file found; $delete");
			}
		}
	}

	/**
	 * @param string $cmd
	 * @return string
	 * @throws Exception_NotFound
	 * @throws Exception_Semantics
	 */
	private function _whichCommand(string $cmd): string {
		try {
			return $this->application->paths->which($cmd);
		} catch (Exception_NotFound $e) {
		}
		$args = [
			'command' => $cmd, 'path' => implode(':', $this->application->commandPath()),
		];
		$message = $this->application->theme('error/update-command-not-found', $args);

		throw new Exception_NotFound($message, $args);
	}

	/**
	 * @param string $filename
	 * @return bool
	 */
	private function isUnpack(string $filename): bool {
		if (StringTools::ends($filename, [
			'.tar.gz', '.tgz', '.tar',
		])) {
			return true;
		}
		if (StringTools::ends($filename, [
			'.zip',
		])) {
			return true;
		}
		return false;
	}

	/**
	 * @param array $data
	 * @return bool|null
	 * @throws Exception_Directory_Create
	 * @throws Exception_Directory_NotFound
	 * @throws Exception_Directory_Permission
	 * @throws Exception_File_NotFound
	 * @throws Exception_File_Permission
	 * @throws Exception_Parameter
	 */
	private function unpack(array $data): bool {
		$filename = $data['filename'] ?? null;
		$temp_file_name = $data['temp_file_name'] ?? null;
		$destination = $data['destination'] ?? null;
		$this->debugLog('Unpacking {filename}', [
			'filename' => $filename,
		]);
		if (StringTools::ends($filename, [
			'.tar.gz', '.tgz', '.tar',
		])) {
			$this->debugLog('Unpacking tar file {filename}', [
				'filename' => $filename,
			]);
			$result = self::unpack_tar($data);
		} elseif (StringTools::ends($filename, [
			'.zip',
		])) {
			$this->debugLog('Unpacking ZIP file {filename}', [
				'filename' => $filename,
			]);
			$result = self::unpack_zip($data);
		} else {
			$full_destination = path($destination, $filename);
			$this->debugLog('Copying directory {temp_file_name} => {full_destination}', [
				'temp_file_name' => $filename, 'full_destination' => $full_destination,
			]);
			if (is_dir($temp_file_name)) {
				Directory::copy($temp_file_name, $full_destination, true);
				$result = true;
			} else {
				$result = copy($temp_file_name, $full_destination);
			}
		}
		if (!$result) {
			return false;
		}
		// Clean up perms
		foreach (Directory::listRecursive($destination) as $f) {
			$path = path($destination, $f);
			chmod($path, is_file($path) ? 0o644 : 0o755);
		}
		return true;
	}

	/**
	 *
	 * @param array $data
	 * @return void
	 * @throws Exception
	 */
	private function updateShare(array $data): void {
		$source = $this->shareSource($data);
		$dest = $this->shareDestination($data);
		if (!$source || !$dest) {
			return;
		}
		if (!$this->needUpdate($dest)) {
			return;
		}

		try {
			$this->debugLog('Copying share directory from {source} to {dest} for module {name}', [
				'source' => $source, 'dest' => $dest, 'name' => $data['name'],
			]);
			Directory::copy($source, $dest, true);
		} catch (Exception_Directory_Create|Exception_Directory_NotFound|Exception_Directory_Permission|Exception_File_NotFound|Exception_File_Permission
		$e) {
			$this->debugLog('failed because of {e} ... rolling back', [
				'e' => $e,
			]);
			$this->rollback($dest);

			throw $e;
		} catch (Exception_Parameter $e) {
			PHP::log($e);
		}
		// $this->postUpdate($data);
	}

	/**
	 * Called recursively to trim down an archive and remove unwanted cruft.
	 * tar supports this on some systems, but we've extended the meaning to allow simple matching of
	 * paths within the archive.
	 *
	 * @param string $temp_directory_name Path of temporary path for work. MUST be a valid directory.
	 * @param string $final_destination The final destination path. MUST be a valid directory.
	 * @param mixed $strip_components Number of diretories to strip, or filename patterns to match/remove
	 * @return boolean
	 * @throws Exception_File_Permission
	 */
	private function stripComponents(string $temp_directory_name, string $final_destination, int|string $strip_components): bool {
		assert(is_dir($temp_directory_name));
		assert(is_dir($final_destination));

		$match = null;
		if (is_numeric($strip_components)) {
			$n_components = intval($strip_components);
			$strip_components = $n_components - 1;
		} else {
			if (empty($strip_components)) {
				$n_components = 0;
			} else {
				$parts = explode('/', $strip_components);
				$n_components = count($parts);
				$match = array_shift($parts);
				$match = $match === '*' ? '/.*/' : '/' . preg_quote($match, '/') . '/';
				$strip_components = implode('/', $parts);
			}
		}
		assert($n_components >= 0);

		if ($n_components > 0) {
			foreach (Directory::ls($temp_directory_name) as $d) {
				$dir = path($temp_directory_name, $d);
				if (is_dir($dir)) {
					if ($match === null || preg_match($match, $d)) {
						self::stripComponents($dir, $final_destination, $strip_components);
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

	/**
	 * @param array $data
	 * @return string|null
	 */
	private function shareDestination(array $data): ?string {
		if (!$this->hasOption('sharePath')) {
			return null;
		}
		$name = $data['name'];
		return path($this->option('sharePath'), $name);
	}

	/**
	 * @param array $data
	 * @return string|null
	 */
	private function shareSource(array $data): ?string {
		if (!$this->hasOption('sharePath')) {
			return null;
		}
		$path = $data['path'];
		$sharePath = $data['sharePath'] ?? null;
		if ($sharePath && is_dir($sharePath)) {
			return $sharePath;
		}
		$sharePath = path($path, 'share');
		if ($sharePath && is_dir($sharePath)) {
			return $sharePath;
		}
		return null;
	}

	/**
	 * @param array $data
	 * @param string $destination
	 * @return string
	 */
	private function computeDestination(array $data, string $destination): string {
		$application_root = $data['application_root'];
		$path = $data['path'];
		$name = $data['name'];
		if (str_starts_with($destination, $path)) {
			$this->application->logger->error('Module {name} uses module path for updates - deprecated! Use application_root instead.', compact('name'));
			$destination = StringTools::removePrefix($destination, $path);
		}
		if (str_starts_with($destination, $application_root)) {
			$destination = StringTools::removePrefix($destination, $application_root);
		}
		/* Disable share-path for now */ // 		if (trim($destination, '/') === 'share' && $this->hasOption('share-path')) {
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
	private function unpack_tar(array $data): bool {
		$filename = $data['filename'];
		$destination = $data['destination'];
		$strip_components = $data['strip_components'] ?? null;
		$name = $data['name'] ?? null;

		$args = [];
		$args[] = $this->_whichCommand('tar');
		$args[] = StringTools::ends($filename, 'gz') ? 'zxf' : 'xf';
		$args[] = $data['temp_file_name'];
		$actual_destination = $destination;
		if (is_int($strip_components) || is_string($strip_components)) {
			$destination = Directory::depend($this->application->paths->temporary($name . '-' . $this->application->process->id()));
		} else {
			$strip_components = 0;
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
	private function unpack_zip(array $data): bool {
		$filename = $temp_file_name = $destination = $strip_components = $name = null;
		extract($data, EXTR_IF_EXISTS);
		$args = [];
		$args[] = $this->_whichCommand('unzip');
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
	 * Unpack generic command.
	 *
	 * @param array $args
	 * @param string $destination
	 * @param string $actual_destination
	 * @param int|string $strip_components
	 * @return bool
	 * @throws Exception_Directory_NotFound
	 * @throws Exception_Directory_Permission
	 * @throws Exception_File_Permission
	 */
	private function _unpack(array $args, string $destination, string $actual_destination, int|string $strip_components): bool {
		$command = implode(' ', $args);
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
			$this->debugLog('Stripping components {strip_components}', [
				'strip_components' => $strip_components,
			]);
			return $this->stripComponents($destination, $actual_destination, $strip_components);
		}
		return true;
	}

	/**
	 * @param $destination
	 * @return bool
	 */
	private function needUpdate($destination): bool {
		if (!$this->repo) {
			return true;
		}
		return $this->repo->needUpdate($destination);
	}

	/**
	 * @param string $destination
	 * @return void
	 */
	private function rollback(string $destination): void {
		if (!$this->repo) {
			return;
		}
		$this->repo->rollback($destination);
	}
}
