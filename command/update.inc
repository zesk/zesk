<?php

/**
 * Update code from remote sources in ${module}.module.conf
 * @category Management
 */
class Command_Update extends Command_Base {
	protected $option_types = array(
		'share-path' => 'path',
		'source-control' => 'string',
		'dry-run' => 'boolean',
		'skip-delete' => 'boolean',
		'skip-database' => 'boolean',
		'timeout' => 'integer',
		'list' => 'boolean',
		'force' => 'boolean',
		'force-check' => 'boolean',
		'*' => 'string'
	);
	protected $option_help = array(
		'share-path' => 'Copy all updated modules files to this directory instead of the default Controller_Share directories.',
		'source-control' => 'Uses source control to check in recent updates.',
		'dry-run' => 'Show what would have happened without actually doing it.',
		'skip-delete' => 'Skip DELETE_AFTER values in module configuration files.',
		'skip-database' => 'Do not store results in a local database, or load local database of last request times.',
		'timeout' => 'Timeout in milliseconds for URL downloads - this is the total time to donwload resulting packages, so keep it reasonable.',
		'list' => 'List all modules which would update',
		'force' => 'Force updates regardless if local copies match most recent downloaded copies',
		'force-check' => 'Force check for all modules regardless of when last checked.',
		'*' => 'A list of modules to download and update'
	);
	protected $option_defaults = array(
		"timeout" => 120000
	);
	
	/*
	 * @var Repository
	 */
	protected $repo = null;
	
	/**
	 * Retrieve a list of modules from the command line
	 */
	private function modules_from_command_line(array $module_options) {
		$modules = array();
		do {
			$module = $this->get_arg("module");
			$this->verbose_log("Updating command line module \"{module}\"", array(
				"module" => $module
			));
			try {
				$data = Module::load($module, $module_options);
			} catch (Exception_Directory_NotFound $e) {
				log::error("No such module $module found.");
				continue;
			}
			$data['debug'] = _dump($data);
			if (is_array($data) && array_key_exists('configuration', $data)) {
				$modules[$module] = $data;
			} else {
				log::warning("{name} does not have an associated configuration file {debug}", $data);
			}
		} while ($this->has_arg());
		return $modules;
	}
	
	/**
	 * Retrieve a list of modules from available paths
	 */
	private function modules_from_module_paths($module_options) {
		$modules = array();
		$paths = zesk::module_path();
		if (count($paths) === 0) {
			$this->verbose_log("No module paths configured");
		}
		$sources = array();
		foreach ($paths as $path) {
			$globbed = array_merge(glob(path($path, "*/*.module.conf")), glob(path($path, "*/*/*.module.conf")), glob(path($path, "*/*.module.json")), glob(path($path, "*/*/*.module.json")));
			if (is_array($globbed)) {
				$count = count($globbed);
				$this->verbose_log("Module path {path}: {count} {modules}", array(
					"path" => $path,
					"count" => $count,
					"modules" => lang::plural(__("module"), $count)
				));
				foreach ($globbed as $glob) {
					$module = str::unprefix(dirname($glob), rtrim($path, "/") . "/");
					$data = Module::load($module, $module_options);
					$debug['debug'] = _dump($data);
					if (is_array($data) && array_key_exists('configuration_file', $data)) {
						$modules[$module] = $data;
						$this->verbose_log("Module {name} loaded configuration {configuration_file}", $debug + $data);
					} else {
						log::warning("{name} does not have an associated configuration file", $debug + $data);
					}
				}
			} else {
				$this->verbose_log("Module path {path}: no modules found", array(
					"path" => $path
				));
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
		$module_options = array(
			'load' => false
		);
		
		if ($this->has_arg()) {
			$modules = $this->modules_from_command_line($module_options);
		} else if ($this->option_bool('all')) {
			$modules = $this->modules_from_module_paths($module_options);
		} else {
			$modules = $this->application->module->load();
		}
		if (count($modules) === 0) {
			$this->error("No modules found to update");
			return array();
		}
		
		$this->verbose_log("Will update {count} {modules}", array(
			"count" => count($modules),
			"modules" => lang::plural(__("module"), count($modules))
		));
		return $modules;
	}
	
	/**
	 * Main comman entry point
	 *
	 * {@inheritDoc}
	 *
	 * @see Command::run()
	 */
	function run() {
		$this->inherit_global_options();
		
		if ($this->help) {
			$this->usage();
			return;
		}
		if (!$this->option_bool("skip-database")) {
			// Options loaded from configuration file
			$this->verbose_log("Loading update.conf");
			$this->configure("update", true);
		}
		
		if ($this->has_option('source-control')) {
			$vc = $this->option('source-control');
			$this->repo = Repository::factory($vc);
			if (!$this->repo) {
				$this->usage("No version-control of type \"{type}\" available", array(
					"type" => $vc
				));
				return 1;
			} else {
				$this->verbose_log("Using repository {type}", array(
					"type" => $vc
				));
			}
		}
		
		$this->app_data = array(
			'application_root' => $this->application->application_root()
		);
		$modules = $this->modules_to_update();
		
		$result = 0;
		foreach ($modules as $module => $module_data) {
			if (!$this->_update_module($module, $module_data)) {
				$result = 1;
			}
		}
		return $result;
	}
	
	/**
	 * Update a single module
	 *
	 * @param string $module        	
	 * @param array $module_data        	
	 * @return array
	 */
	private function _update_module($module, array $module_data) {
		$force = $this->option_bool('force');
		$force_check = $this->option_bool('force-check');
		$now = time();
		$data = avalue($module_data, 'configuration');
		if (!is_array($data)) {
			$this->log(__("### module {name} does not have configuration information: {configuration_file}", $module_data + array(
				'configuration_file' => '-not specified-'
			)));
			return true;
		}
		if (!arr::has_any($data, 'url;urls;versions;composer')) {
			return true;
		}
		if ($this->option_bool('list')) {
			$this->log($module);
			return true;
		}
		$this->log("Updating $module");
		if (arr::has($data, "composer")) {
			return $this->composer_update($data);
		}
		$state_data = zesk::geta('MODULE_' . $module);
		if (!$force) {
			$checked = avalue($state_data, 'CHECKED', null);
			$checked_time = strtotime($checked);
			$interval = zesk::get('Command_Update::check_interval', 24 * 60 * 60);
			if ($checked_time > $now - $interval) {
				$this->verbose_log("$module checked less than " . lang::duration_string($interval, "hour") . " ago" . ($force_check ? "- checking anyway" : ""));
				if (!$force_check) {
					return true;
				}
			} else {
				// echo "$checked_time > $now - $interval (" . ($now - $interval) . ")\n";
			}
		}
		$edits = $this->fetch($this->app_data + $state_data + $module_data + $data);
		$result = is_array($edits) ? true : false;
		if ($this->config) {
			$date = gmdate('Y-m-d H:i:s');
			if ($edits === null) {
				$this->verbose_log("$module uptodate\n");
				$edits = array();
				$edits['CHECKED'] = $date;
			} else if ($edits instanceof Exception) {
				$message = $edits->getMessage();
				$edits = array();
				$edits['FAILED_MESSAGE'] = $message;
				$edits['FAILED'] = $date;
				$this->verbose_log("$module failed: $message\n");
			} else {
				$edits = is_array($edits) ? $edits : array();
				$edits['CHECKED'] = gmdate('Y-m-d H:i:s');
				$this->verbose_log("$module updated\n");
			}
			$source = $module_data['configuration_file'];
			conf::edit($this->config, array(
				'MODULE_' . $module => $edits
			), self::update_configuration_options());
		}
		return $result;
	}
	private function composer_command() {
		if ($this->has_option("composer_command")) {
			return $this->option("composer_command");
		}
		$composer_phar = null;
		if ($this->has_option("composer_phar")) {
			$composer_phar = $this->option("composer_phar");
		} else {
			$composer_phar = zesk::which("composer.phar");
		}
		if ($composer_phar) {
			return $this->option("php_command", "/usr/bin/env php $composer_phar");
		}
		throw new Exception_Configuration(__CLASS__ . "::composer_phar", "Need to set composer_command or composer_phar {class}", array(
			"class" => get_class($this)
		));
	}
	/**
	 * Update composer data as part of a module
	 *
	 * @param array $data        	
	 */
	private function composer_update(array $data) {
		$composer = null;
		extract($data, EXTR_IF_EXISTS);
		
		$composer_command = $this->composer_command();
		if (arr::has($composer, "require")) {
			$requires = to_list($composer['require']);
			$pwd = getcwd();
			chdir($this->application->application_root());
			foreach ($requires as $require) {
				if (!is_string($require)) {
					log::error("Module {name} {conf_path} composer.require is not a string? {type}", array(
						"type" => type($require)
					) + $data);
					continue;
				}
				if ($this->option_bool("dry-run")) {
					$this->log("Would run command: $composer_command require {require}", array(
						"require" => $require
					));
				} else {
					$this->exec("$composer_command require {require}", array(
						"require" => $require
					));
				}
			}
		}
	}
	private static function update_configuration_options() {
		$options = array(
			'lower' => true,
			'trim' => true,
			'multiline' => true,
			'unquote' => '\'\'""'
		);
		return $options;
	}
	private function _fetch_url($url) {
		$client = new Net_HTTP_Client($url);
		$minutes = 5; // 2 minutes total for client to run
		$client->timeout($minutes * 60000);
		$temp_file_name = file::temporary();
		$client->follow_location(true);
		$client->user_agent('Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:12.0) Gecko/20100101 Firefox/12.0');
		$client->destination($temp_file_name);
		$this->verbose_log("Downloading $url ... ");
		$client->go();
		$response_code = $client->response_code_type();
		if ($response_code === 2) {
			$filename = $client->filename();
			return array(
				$temp_file_name,
				$filename
			);
		}
		if ($response_code === 4) {
			throw new Exception_NotFound("URL {url} no longer exists", compact("url"));
		}
		throw new Exception_System("Server {url} temporarily down or returning an error? {response_code}", compact("url", "response_code"));
	}
	private function urls_to_fetch(array &$data) {
		$path = $name = $versions = $url = $urls = $strip_components = $hashes = $source = $destination = $delete_after = null;
		extract(array_change_key_case($data), EXTR_IF_EXISTS);
		if ($versions !== null) {
			$version = zesk::get("Module_$name::version", null);
			if (!$version) {
				$version = last(array_keys($versions));
			}
			$this->log("Updating {name} to version {version}", array(
				"name" => $name,
				"version" => $version
			));
			$data['version'] = $version;
			extract(array_change_key_case($versions[$version]), EXTR_IF_EXISTS);
		}
		if ($urls !== null) {
			if (is_string($urls)) {
				$urls = explode(" ", $urls);
			} else if (!is_array($urls)) {
				throw new Exception_Semantics("URLS should be a list of urls or an array");
			}
		} else if ($url !== null) {
			$urls = array(
				$url
			);
		} else {
			throw new Exception_Configuration($data['configuration_file'], "No URLS or URL set in module {name}", array(
				"name" => $name
			));
		}
		$urls = map($urls, $data);
		$load_urls = array();
		foreach ($urls as $url => $value) {
			if (url::valid($url)) {
				if (is_string($value)) {
					$load_urls[$url] = array(
						'destination' => $value,
						'strip_components' => $strip_components
					);
				} else if (is_array($value)) {
					$load_urls[$url] = array_change_key_case($value) + array(
						'destination' => $destination,
						'strip_components' => $strip_components
					);
				}
			} else if (!url::valid($value)) {
				$this->error("$value is not a valid URL");
				return false;
			} else {
				$load_urls[$value] = array(
					'destination' => $destination,
					'strip_components' => $strip_components
				);
			}
		}
		return $load_urls;
	}
	private function fetch(array $data) {
		// $source = $url = $destination = $strip_components = $description = $hashes = null;
		$name = $hashes = $delete_after = null;
		extract(array_change_key_case($data), EXTR_IF_EXISTS);
		if (!is_array($hashes)) {
			$hashes = array();
		}
		$load_urls = $this->urls_to_fetch($data);
		if ($load_urls === false) {
			return null;
		}
		$dry_run = $this->option_bool('dry-run');
		$new_hashes = array();
		
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
				list($temp_file_name, $filename) = $this->_fetch_url($url);
			} catch (Exception $e) {
				$this->error("Updating {url} failed: {message}", array(
					"url" => $url,
					"message" => $e->getMessage()
				));
				return $e;
			}
			
			$do_update = false;
			$new_hash = md5_file($temp_file_name);
			$dest_file = path($destination, $filename);
			if ($this->option_bool('force')) {
				$do_update = true;
				$this->verbose_log('Updating forced');
			} else if (!$this->is_unpack($filename) && !file_exists($dest_file)) {
				$do_update = true;
				$this->verbose_log("Destination file {dest_file} doesn't exist? (filename is {filename})", array(
					"dest_file" => $dest_file,
					"filename" => $filename
				));
			} else {
				$hash = avalue($hashes, $url);
				if ($hash !== $new_hash) {
					$do_update = true;
					$this->verbose_log("Hashes don't match for {dest_file}: {hash} !== {new_hash}", array(
						"dest_file" => $dest_file,
						"hash" => $hash,
						"new_hash" => $new_hash
					));
				} else if (!is_dir($destination) || dir::is_empty($destination)) {
					$do_update = true;
					$this->verbose_log("Destination directory {destination} doesn't exist", array(
						"destination" => $destination
					));
				}
			}
			if (!$do_update) {
				@unlink($temp_file_name);
				continue;
			} else {
				$did_updates = true;
			}
			dir::depend($destination, 0775);
			
			$data['filename'] = $filename;
			$data['temp_file_name'] = $temp_file_name;
			$settings['destination'] = $destination;
			
			if ($this->option_bool('debug')) {
				//echo text::format_array($data['configuration']);
			}
			
			$unpack_result = $this->unpack($settings + $data);
			
			$this->update_share($settings + $data);
			
			@unlink($temp_file_name);
			if ($unpack_result) {
				$new_hashes[$url] = $new_hash;
			}
			try {
				$module = avalue($data, 'module');
				if ($module instanceof Module) {
					$this->log("Running " . get_class($module) . "::hook_updated");
					$module->hook('updated');
				}
			} catch (ReflectionException $e) {
				log::debug("Module_$name was not found ... skipping");
			} catch (Exception_Class_NotFound $e) {
				log::debug("Module_$name was not found ... skipping");
			}
		}
		if (!$this->option_bool('skip-delete') && is_array($delete_after)) {
			$this->_delete_files($destination, $delete_after);
		}
		if ($did_updates) {
			$this->log("{name} updated to latest version.", array(
				"name" => $name
			));
		} else {
			$this->log("{name} is up to date.", array(
				"name" => $name
			));
		}
		if (count($new_hashes) > 0) {
			return array(
				'HASHES' => $new_hashes + $hashes
			);
		}
		$this->verbose_log("$name unchanged");
		return null;
	}
	private function _delete_files($destination, array $files) {
		$delete_files = array();
		foreach ($files as $file) {
			if (strpos($file, "*") !== false) {
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
				$this->verbose_log("Deleted file {delete} does not contain prefix {destination} - skipping", array(
					"delete" => $delete,
					"destination" => $destination
				));
				unset($delete_files[$index]);
			}
		}
		foreach ($delete_files as $delete) {
			if (is_dir($delete)) {
				$this->log("Deleting directory {delete}", array(
					"delete" => $delete
				));
				dir::delete($delete);
			} else if (is_file($delete)) {
				$this->log("Deleting file {delete}", array(
					"delete" => $delete
				));
				unlink($delete);
			} else {
				$this->debug_log("No delete file found; $delete");
			}
		}
	}
	private function _which_command($cmd) {
		$path = zesk::which($cmd);
		if ($path) {
			return $path;
		}
		$args = array(
			"command" => $cmd,
			"path" => implode(":", zesk::command_path())
		);
		throw new Exception_File_NotFound($cmd, map(theme('error/update-command-not-found'), $args));
	}
	private function is_unpack($filename) {
		if (str::ends($filename, array(
			".tar.gz",
			".tgz",
			".tar"
		))) {
			return true;
		}
		if (str::ends($filename, array(
			".zip"
		))) {
			return true;
		}
		return false;
	}
	private function unpack(array $data) {
		$filename = $temp_file_name = $destination = null;
		$this->debug_log("Unpacking {filename}", array(
			"filename" => $filename
		));
		extract($data, EXTR_IF_EXISTS);
		if (str::ends($filename, array(
			".tar.gz",
			".tgz",
			".tar"
		))) {
			$this->debug_log("Unpacking tar file {filename}", array(
				"filename" => $filename
			));
			$result = self::unpack_tar($data);
		} else if (str::ends($filename, array(
			".zip"
		))) {
			$this->debug_log("Unpacking ZIP file {filename}", array(
				"filename" => $filename
			));
			$result = self::unpack_zip($data);
		} else {
			$full_destination = path($destination, $filename);
			$this->debug_log("Copying directory {temp_file_name} => {full_destination}", array(
				"temp_file_name" => $filename,
				'full_destination' => $full_destination
			));
			if (is_dir($temp_file_name)) {
				$result = dir::copy($temp_file_name, $full_destination, true);
			} else {
				$result = copy($temp_file_name, $full_destination);
			}
		}
		if (!$result) {
			return $result;
		}
		// Clean up perms
		foreach (dir::list_recursive($destination) as $f) {
			$path = path($destination, $f);
			chmod($path, is_file($path) ? 0644 : 0755);
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
		if (!$this->pre_update($dest)) {
			return false;
		}
		try {
			$this->debug_log("Copying share directory from {source} to {dest} for module {name}", array(
				"source" => $source,
				"dest" => $dest,
				"name" => $data['name']
			));
			dir::copy($source, $dest, true);
		} catch (Exception $e) {
			$this->debug_log("Failed because of {e} ... rolling back", array(
				"e" => $e
			));
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
		assert("$n_components >= 0");
		
		if ($n_components > 0) {
			foreach (dir::ls($temp_directory_name) as $d) {
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
			$debug = $this->debug;
			if ($debug) {
				log::debug("strip_components: level=0 Copying $temp_directory_name");
			}
			foreach (dir::ls($temp_directory_name) as $f) {
				$source_path = path($temp_directory_name, $f);
				$dest_path = path($final_destination, $f);
				if ($debug) {
					log::debug("strip_components: Copying $source_path to $dest_path");
				}
				if (is_file($source_path)) {
					if (!copy($source_path, $dest_path)) {
						throw new Exception_File_Permission($dest_path, "rename $source_path to $dest_path");
					}
				} else {
					if (!dir::copy($source_path, $dest_path, true)) {
						dir::delete($dest_path);
						throw new Exception_File_Permission($dest_path, "dir::copy $source_path to $dest_path");
					}
				}
			}
		}
		return true;
	}
	private function share_destination(array $data) {
		if (!$this->has_option('share-path')) {
			return null;
		}
		$name = $data['name'];
		return path($this->option('share-path'), $name);
	}
	private function share_source(array $data) {
		if (!$this->has_option('share_path')) {
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
			log::error("Module {name} uses module path for updates - deprecated! Use application_root instead.", compact("name"));
			$destination = str::unprefix($destination, $path);
		}
		if (begins($destination, $application_root)) {
			$destination = str::unprefix($destination, $application_root);
		}
		/* Disable share-path for now */
		// 		if (trim($destination, '/') === 'share' && $this->has_option('share-path')) {
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
		$args = array();
		$args[] = $this->_which_command('tar');
		$args[] = str::ends($filename, "gz") ? "zxf" : "xf";
		$args[] = $temp_file_name;
		$actual_destination = $destination;
		if ($strip_components) {
			$destination = dir::temporary($name . '-' . zesk::pid());
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
		$args = array();
		$args[] = $this->_which_command('unzip');
		$args[] = '-o';
		$args[] = $temp_file_name;
		$actual_destination = $destination;
		if ($strip_components) {
			$destination = dir::temporary($name . '-' . zesk::pid());
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
				dir::delete($destination);
			}
			$this->error("$command failed:\n$output");
			return false;
		}
		if ($strip_components) {
			$this->debug_log("Stripping components {strip_components}", array(
				"strip_components" => $strip_components
			));
			return $this->strip_components($destination, $actual_destination, $strip_components);
		}
		return true;
	}
	private function pre_update($destination) {
		if (!$this->repo) {
			return true;
		}
		return $this->repo->pre_update($destination);
	}
	private function rollback($destination) {
		if (!$this->repo) {
			return true;
		}
		return $this->repo->rollback($destination);
	}
	private function post_update($destination) {
		if (!$this->repo) {
			return true;
		}
		return $this->repo->rollback($destination);
	}
}
