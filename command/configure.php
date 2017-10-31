<?php
/**
 *
 */
namespace zesk;

/**
 * Automatically keep a series of files and directories in sync between users, with security checks
 * for superusers, and simplify version control of remote systems.
 *
 * Basically, if you deploy software to remote systems, this lets you keep your configuration files
 * in a source repository and copy them into the
 * appropriate locations without too much extra work
 *
 * @alias sync
 *
 * @author kent
 *
 */
class Command_Configure extends Command_Base {
	protected $option_types = array(
		"non-interactive" => "boolean",
		"environment-file" => "string",
		"host-setting-name" => "string"
	);
	
	/**
	 * Whether the configuration should be saved
	 *
	 * @var boolean
	 */
	private $changed = null;
	
	/**
	 * Whether anything was skipped (out of sync)
	 *
	 * @var integer
	 */
	private $incomplete = 0;
	
	/**
	 *
	 * @var string
	 */
	private $host_path = null;
	
	/**
	 *
	 * @var string
	 */
	private $uname = null;
	
	/**
	 *
	 * @var string
	 */
	private $low_uname = null;
	
	/**
	 *
	 * @var string
	 */
	private $username = null;
	
	/**
	 * List of known host configurations
	 *
	 * @var array
	 */
	private $possible_host_configurations = array();
	
	/**
	 * Map from uname => host configurations
	 *
	 * @var array
	 */
	private $alias_file = null;
	
	/**
	 * List of host configurations
	 *
	 * @var array
	 */
	private $host_configurations = array();
	
	/**
	 * List of host paths for this host
	 *
	 * @var array
	 */
	private $host_paths = array();
	
	/**
	 * Variables to map when copying files around, etc.
	 *
	 * @var array
	 */
	private $variable_map = array();
	
	/**
	 *
	 * @var integer
	 */
	protected $current_uid = null;
	
	/**
	 *
	 * @var integer
	 */
	protected $current_gid = null;
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Command::run()
	 */
	protected function run() {
		$this->completion_function();
		
		$this->configure("configure", true);
		
		$this->uname = System::uname();
		$this->low_uname = strtolower($this->uname);
		$this->username = avalue($_SERVER, 'USER');
		
		$this->variable_map['home'] = $this->application->paths->home();
		$this->variable_map['uname'] = $this->uname;
		$this->variable_map['low_uname'] = $this->low_uname;
		$this->variable_map['user'] = $this->username;
		$this->variable_map['zesk_application_root'] = $this->application->path(); // Deprecated
		$this->variable_map['application_root'] = $this->application->path();
		$this->variable_map['zesk_root'] = $this->application->zesk_root();
		$this->variable_map['user'] = $this->username;
		$this->variable_map['username'] = $this->username; // Deprecate?
		
		$this->log("Configuration synchronization for: {uname}, user: {user}", $this->variable_map);
		$this->determine_environment_file();
		if (!$this->determine_host_path_setting_name()) {
			return 1;
		}
		$this->determine_host_name();
		
		$this->save_configuration_changes();
		
		$this->incomplete = 0;
		if (!$this->configure_user()) {
			return 99;
		}
		$this->verbose_log("Success");
		return 0;
	}
	private function determine_environment_file() {
		$value = $this->environment_file;
		$times = 0;
		$this->completions = Directory::ls("/etc/", "/(.conf|.sh)$/", true);
		while (empty($value) || !is_file($value)) {
			if ($times > 2) {
				echo __("System settings file is a BASH and Zesk conf::load parsable file which contains\na global which points to this host's configuration directory.\n\n");
			}
			$value = trim($this->prompt(__("Path to system settings file: ")));
			++$times;
			$this->changed = true;
		}
		$this->variable_map['environment_file'] = $this->environment_file;
		return $this->environment_file = $value;
	}
	private function determine_host_path_setting_name() {
		$value = $this->host_setting_name;
		$times = 0;
		$output = false;
		while (is_array($dirs = $this->load_dirs($output)) && !array_key_exists($value, $dirs)) {
			if (count($dirs) === 0) {
				echo __("No possible directory settings in {environment_file}, please edit and add variable which points to a local directory with host information", $this->option());
			}
			if ($times > 2) {
				echo __("The system setting will point to a directory of host configurations to keep in sync.\n\n");
			}
			$this->completions = array_keys($dirs);
			$value = trim($this->prompt(__("Name of system setting: ")));
			++$times;
			$this->changed = true;
			$output = true;
		}
		$this->host_setting_name = $value;
		$this->host_path = $dirs[$value];
		$this->variable_map['host_path'] = $this->host_path;
		if (!is_dir($this->host_path)) {
			$this->error("Host path does not exist? {host_path}", array(
				"host_path" => $this->host_path
			));
			return null;
		}
		return $value;
	}
	private function load_conf($path) {
		$conf = array();
		Configuration_Parser::factory(File::extension($path), File::contents($path), new Adapter_Settings_Array($conf), array(
			"lower" => false
		))->process();
		return $conf;
	}
	private function save_conf($path, array $settings) {
		$conf = array();
		$contents = File::contents($path);
		$parser = Configuration_Parser::factory(File::extension($path), $contents, new Adapter_Settings_Array($conf));
		$editor = $parser->editor($contents);
		return File::put($path, $editor->edit($settings));
	}
	private function load_dirs($output = false) {
		$env = $this->load_conf($this->environment_file);
		$this->variable_map += array_change_key_case($env);
		$dirs = array();
		foreach ($env as $name => $value) {
			if ((begins($value, "/") || begins($value, ".")) && is_dir($value)) {
				$dirs[$name] = $value;
			} else {
				$possibilities[] = $name;
			}
		}
		if ($output) {
			$this->log(__("Non-directory settings: {possibilities}", array(
				"possibilities" => implode(" ", $possibilities)
			)));
			$this->log(__("Available settings: {dirs}", array(
				"dirs" => implode(" ", array_keys($dirs))
			)));
		}
		return $dirs;
	}
	private function determine_host_name() {
		$this->possible_host_configurations = arr::unsuffix(Directory::ls($this->host_path), "/", true);
		$this->alias_file = path($this->host_path, "aliases.conf");
		$__ = array(
			"alias_file" => $this->alias_file
		);
		$this->verbose_log("Alias file is {alias_file}", $__);
		$uname = $this->uname;
		if (!is_file($this->alias_file)) {
			self::file_put_contents_inherit($this->alias_file, "$uname=[]");
			$this->verbose_log("Created empty {alias_file}", $__);
		}
		$aliases = array();
		while (!is_array($host_configs = avalue($aliases = $this->load_conf($this->alias_file), $this->low_uname)) || count(array_diff($host_configs, $this->possible_host_configurations)) !== 0) {
			$configs = $this->determine_host_configurations();
			if ($this->prompt_yes_no(__("Save changes to {alias_file} for {uname}? ", $__ + $this->variable_map))) {
				
				$this->save_conf($this->alias_file, array(
					$uname => $configs
				));
				$this->log("Changed {alias_file}", array(
					"alias_file" => $this->alias_file
				));
			}
		}
		$this->host_configurations = $host_configs;
		return $host_configs;
	}
	private function determine_host_configurations() {
		$this->completions = $possible_host_configurations = $this->possible_host_configurations;
		do {
			$message = __("Host configurations: {configs}", array(
				"configs" => implode(" ", $possible_host_configurations)
			)) . "\n\n";
			$message .= __("Enter a list of configurations separated by space") . "\n";
			$host_configurations = $this->prompt("$message\n> ");
			$host_configurations = explode(" ", preg_replace("/\s+/", " ", trim($host_configurations)));
		} while (count(array_diff($host_configurations, $possible_host_configurations)) !== 0);
		$this->log("Will add host configuration for host {host}: {host_configurations}", array(
			"host" => $this->host,
			"host_configurations" => implode(" ", $host_configurations)
		));
		return $host_configurations;
	}
	private function save_configuration_changes() {
		if ($this->changed) {
			$__ = array(
				"config" => $this->config
			);
			if ($this->prompt_yes_no(__("Save changes to {config}? ", $__))) {
				$this->save_conf($this->config, arr::kprefix($this->options_include("environment_file;host_setting_name"), __CLASS__ . "::"));
				$this->log("Wrote {config}", $__);
			}
		}
	}
	private function configure_user() {
		$username = $this->username;
		$paths = array();
		foreach ($this->host_configurations as $host) {
			$paths[] = path($this->host_path, $host);
		}
		$this->verbose_log(__("Configuration paths:\n\t{paths}", array(
			"paths" => implode("\n\t", $paths)
		)));
		$this->host_paths = $paths;
		
		$pattern = $this->option("user_configuration_file", "users/{user}/configure");
		$suffix = $this->map($pattern);
		$files = File::find_all($paths, $suffix);
		$this->log(__("Configuration files:\n\t{files}", array(
			"files" => implode("\n\t", $files)
		)));
		list($this->current_uid, $this->current_gid) = $this->current_uid_gid();
		
		foreach ($files as $file) {
			$this->variable_map['current_host_path'] = rtrim(str::unsuffix($file, $suffix), "/");
			$this->variable_map['self_path'] = dirname($file);
			$this->variable_map['self'] = $file;
			$this->verbose_log("Processing file {file}", compact("file"));
			$contents = File::contents($file);
			$contents = Text::remove_line_comments($contents, "#", false);
			$lines = arr::trim_clean(explode("\n", $contents));
			foreach ($lines as $line) {
				$line = preg_replace("/\s+/", " ", $line);
				list($command, $raw_arguments) = pair($line, " ", $line, null);
				$command = PHP::clean_function($command);
				$raw_arguments = preg_replace("/\s+/", " ", trim($raw_arguments));
				$arguments = $this->map($raw_arguments);
				$method = "_command_$command";
				$__ = compact("command", "raw_arguments", "arguments");
				if (method_exists($this, $method)) {
					$this->verbose_log("Running command {command} {raw_arguments} => {arguments}", $__);
					$result = call_user_func_array(array(
						$this,
						$method
					), explode(" ", $arguments));
					if (is_bool($result) && $result === false) {
						$this->error("Command failed ... aborting.");
						return false;
					} else {
						$this->verbose_log("Command {command} was successful.", $__);
					}
				} else {
					$this->error("Unknown command {command} ({raw_arguments})", $__);
				}
			}
		}
		return true;
	}
	private function current_uid_gid() {
		global $zesk;
		/* @var $zesk \zesk\Kernel */
		return array(
			intval(implode("\n", $zesk->process->execute("id -u"))),
			intval(implode("\n", $zesk->process->execute("id -g")))
		);
	}
	
	/**
	 *
	 * @param unknown $target
	 * @param unknown $want_owner
	 * @param unknown $want_mode
	 * @return boolean
	 */
	private function handle_owner_mode($target, $want_owner = null, $want_mode = null) {
		$new_user = null;
		$new_group = null;
		$stats = File::stat($target, "");
		$__['target'] = $target;
		$__['want_owner'] = $want_owner;
		$__['want_mode'] = $want_mode;
		$__['old_mode'] = $old_mode = $stats['perms']['octal0'];
		$__['old_user'] = $stats['owner']['owner'];
		$__['old_group'] = $stats['owner']['group'];
		if (!empty($want_owner)) {
			$this->verbose_log("Want owner of {target} to be {want_owner} ...", $__);
			list($want_user, $want_group) = pair($want_owner, ":", $want_owner, null);
			$__['new_user'] = $want_user;
			$__['new_group'] = $want_group;
			if (!empty($want_user)) {
				if (is_numeric($want_user)) {
					if (intval($want_user) !== intval($stats['owner']['uid'])) {
						$new_user = $want_user;
					}
				} else {
					if (strval($want_user) !== strval($stats['owner']['owner'])) {
						$new_user = $want_user;
					}
				}
			}
			if (!empty($want_group)) {
				if (is_numeric($want_group)) {
					if (intval($want_group) !== intval($stats['owner']['gid'])) {
						$new_group = $want_group;
					}
				} else {
					if (strval($want_group) !== strval($stats['owner']['group'])) {
						$new_group = $want_group;
					}
				}
			}
		}
		$output = false;
		if ($new_user || $new_group) {
			if ($this->current_uid !== 0) {
				$this->error("Unable to change mode of {target}, run command as root:", $__);
				echo "# chown $want_owner $target\n";
			} else {
				if (!$this->prompt_yes_no(__("Change owner of {target} to {want_owner} (old {old_user}:{old_group}) ?", $__))) {
					return false;
				}
				if ($new_user) {
					if (!chown($target, $new_user)) {
						$this->error("Unable to chown {target} to {new_user} (old user {old_user})", $__);
						return false;
					}
					$this->verbose_log("Changed owner of {target} to {new_user} (old user {old_user})", $__);
				}
				if ($new_group) {
					if (!chgrp($target, $new_group)) {
						$this->error("Unable to chgrp {target} to {new_group} (old group {old_group})", $__);
						return false;
					}
					$this->verbose_log("Changed group of {target} to {new_group} (old group {old_group})", $__);
				}
			}
		}
		if (!empty($want_mode)) {
			$this->verbose_log("Want mode of {target} to be {want_mode} ...", $__);
			if ($old_mode !== $want_mode) {
				if (!$this->prompt_yes_no(__("Change permissions of {target} to {want_mode} (old mode {old_mode})?", $__))) {
					return false;
				}
				if (!chmod($target, $__['decimal_want_mode'] = octdec($want_mode))) {
					$this->error("Unable to chmod {target} to {want_mode} (decimal: {decimal_want_mode})", $__);
					return false;
				}
			}
		}
		return true;
	}
	
	/**
	 *
	 * @param unknown $target
	 * @param unknown $owner
	 * @param unknown $mode
	 */
	private function _command_mkdir($target, $owner = null, $mode = null) {
		$__['target'] = $target;
		if (!is_dir($target)) {
			if (!$this->prompt_yes_no(__("Create directory {target}?", $__))) {
				return false;
			}
			if (!mkdir($target, null, true)) {
				$this->error("Unable to create directory {target}", $__);
				return false;
			}
		}
		return $this->handle_owner_mode($target, $owner, $mode);
	}
	
	/**
	 *
	 * @param unknown $symlink
	 * @param unknown $file
	 */
	private function _command_symlink($symlink, $file) {
		$__ = compact("symlink", "file");
		if (!is_dir($file) && !is_file($file)) {
			$this->error("Symlink {symlink} => {file}: File does not exist", $__);
			return false;
		}
		$this->verbose_log("Symlink {symlink} => {file}", $__);
		if (!is_link($symlink)) {
			if (file_exists($symlink)) {
				$bytes = filesize($symlink);
				if (!$this->prompt_yes_no(__("Symlink to create \"{symlink}\" exists ({bytes} bytes), delete?", $__))) {
					return false;
				}
				File::unlink($symlink);
			} else if (is_dir($symlink)) {
				if (!$this->prompt_yes_no(__("Symlink to create \"{symlink}\" is already a directory, delete?", $__))) {
					return false;
				}
				Directory::delete($symlink);
			}
			if (!$this->prompt_yes_no(__("Create symbolic link \n\t{symlink} => {file}\n?", $__))) {
				return false;
			}
		} else {
			if (($oldlink = readlink($symlink)) === $file) {
				return true;
			}
			if (!$this->prompt_yes_no(__("Symlink {symlink} points to {oldfile}, update to point to correct {file}?", compact("old_file") + $__))) {
				return false;
			}
			File::unlink($symlink);
		}
		if (!symlink($file, $symlink)) {
			$this->error("Creating symlink {symlink} to {file} failed?", $__);
			return false;
		}
		return true;
	}
	
	/**
	 *
	 * @param string $source
	 * @param string $destination
	 */
	private function _command_file_catenate($source, $destination, $flags = null) {
		$flags = ($flags !== null) ? arr::flip_assign(explode(",", strtolower(strtr($flags, ";", ","))), true) : array();
		$sources = File::find_all($this->host_paths, $source);
		$__ = array(
			"source" => $source,
			"host_paths" => $this->host_paths
		);
		if (count($sources) === 0) {
			$this->verbose_log("No file {source} found in {host_paths}", $__);
			$this->completions = arr::suffix($this->host_paths, "/" . str::unprefix($source, "/"));
			$__ = array(
				"source" => $source,
				"completions" => implode(" ", $this->completions)
			);
			$conf = trim($this->prompt(__("Create {source}? ({completions})", $__)));
			if (in_array($conf, $this->completions)) {
				$__['conf'] = $conf;
				$this->changed++;
				$this->log("Writing {conf} with empty file for {source}", $__);
				self::file_put_contents_inherit($conf, "");
				try {
					File::copy_uid_gid(dirname($conf), $conf);
				} catch (Exception $e) {
				}
			}
			return true;
		} else {
			$this->verbose_log("Found files {sources}", array(
				"sources" => implode(" ", $sources)
			));
		}
		$content = "";
		$no_map = avalue($flags, "no-map", false);
		$no_trim = avalue($flags, "no-trim", false);
		foreach ($sources as $file) {
			$file_content = File::contents($file);
			if (!$no_map) {
				$file_content = $this->map($file_content);
			}
			if (!$no_trim) {
				$file_content = trim($file_content) . "\n";
			}
			$content .= $file_content;
		}
		if (trim(File::contents($destination)) === trim($content)) {
			return true;
		}
		$temp_file = File::temporary("temp");
		file_put_contents($temp_file, $content);
		switch ($this->_files_differ_helper($temp_file, $destination, $source)) {
			case "source":
				return self::file_put_contents_inherit($destination, $content);
			case "destination":
				$default_source = path(last($this->host_paths), $source);
				$this->verbose_log("Copy {source} to {default_source}", compact("source", "default_source"));
				return self::copy_file_inherit($destination, $default_source);
		}
		return null;
	}
	private function map($string) {
		return map($string, $this->variable_map, true);
	}
	/**
	 *
	 * @param unknown $source
	 * @param unknown $destination
	 */
	private static function copy_file_inherit($source, $destination) {
		if (!copy($source, $destination)) {
			return false;
		}
		try {
			File::copy_uid_gid(dirname($destination), $destination);
		} catch (Exception $e) {
		}
		return true;
	}
	
	/**
	 *
	 * @param unknown $destination
	 * @param unknown $content
	 */
	private static function file_put_contents_inherit($destination, $content) {
		if (!file_put_contents($destination, $content)) {
			return false;
		}
		try {
			File::copy_uid_gid(dirname($destination), $destination);
		} catch (Exception $e) {
		}
		return true;
	}
	
	/**
	 *
	 * @param unknown $source
	 * @param unknown $destination
	 */
	private function _command_file($source, $destination, $want_owner = null, $want_mode = null) {
		$__ = compact("source", "destination", "want_owner", "want_mode");
		if (is_link($destination)) {
			if (!$this->prompt_yes_no(__("Target {destination} is a link, replace with {source} as a file?", $__))) {
				return false;
			}
			File::unlink($destination);
			if (!self::copy_file_inherit($source, $destination)) {
				return false;
			}
			return $this->handle_owner_mode($destination, $want_owner, $want_mode);
		}
		try {
			$this->application->process->execute("diff -w {0} {1}", $source, $destination);
			return $this->handle_owner_mode($destination, $want_owner, $want_mode);
		} catch (Exception_Command $e) {
			// Not the same
		}
		switch ($this->_files_differ_helper($source, $destination)) {
			case "source":
				$result = self::copy_file_inherit($source, $destination);
				if (!$result) {
					return $result;
				}
				return $this->handle_owner_mode($destination, $want_owner, $want_mode);
			case "destination":
				$result = self::copy_file_inherit($destination, $source);
				if (!$result) {
					return $result;
				}
				return $this->handle_owner_mode($destination, $want_owner, $want_mode);
		}
		$__ = compact("source", "destination");
		if (!file_exists($source)) {
			$this->verbose_log(is_dir(dirname($source)) ? "Source {source} does not exist" : "Source {source} does not exist, nor does its parent directory", $__);
		}
		if (!file_exists($destination)) {
			$this->verbose_log(is_dir(dirname($destination)) ? "Destination {destination} does not exist" : "Destination {destination} does not exist, nor does its parent directory", $__);
		}
		
		return null;
	}
	
	/**
	 *
	 * @param unknown $source
	 * @param unknown $destination
	 * @param unknown $source_name
	 * @param unknown $destination_name
	 */
	private function _files_differ_helper($source, $destination, $source_name = null, $destination_name = null) {
		if ($source_name === null) {
			$source_name = $source;
		}
		if ($destination_name === null) {
			$destination_name = $destination;
		}
		$this->log("Files differ: < $source_name > $destination_name");
		try {
			$this->application->process->execute_arguments("diff {0} {1}", array(
				$source,
				$destination
			), true);
		} catch (Exception $e) {
		}
		$this->completions = array(
			"source",
			"<",
			"destination",
			"skip"
		);
		switch (trim($this->prompt("Which is right?\n< source, > destination, or skip? (<,source,>,destination,skip) "))) {
			case "<":
			case "source":
				$this->log("Copying {source_name} to {destination_name}", compact("source_name", "destination_name"));
				$this->changed = true;
				return "source";
			case ">":
			case "destination":
				$this->log("Copying {destination_name} to {source_name}", compact("source_name", "destination_name"));
				$this->changed = true;
				return "destination";
			default :
				$this->log("skipping ...");
				$this->incomplete++;
				return null;
		}
	}
	
	/**
	 * Pass a list of variables which MUST be defined to continue
	 */
	private function _command_defined() {
		$args = func_get_args();
		$not_defined = array();
		foreach ($args as $arg) {
			if (!array_key_exists(strtolower($arg), $this->variable_map)) {
				$not_defined[] = $arg;
			}
		}
		if (count($not_defined) > 0) {
			$this->error("Configure {self} requires the following defined, which are not: {not_defined}\nAll variables: {all_vars}", array(
				"not_defined" => $not_defined,
				"all_vars" => array_keys($this->variable_map)
			) + $this->variable_map);
		} else {
			$this->verbose_log("defined {args} - success", array(
				"args" => implode(" ", $args)
			));
		}
	}
	
	/**
	 *
	 * @param URL $repo Subversion repository URL
	 * @param string $target Directory to check out to
	 */
	private function _command_subversion($repo, $target) {
		/* @var $zesk \zesk\Kernel */
		$app = $this->application;
		$__ = compact("repo", "target");
		try {
			if (!is_dir($target)) {
				if (!$this->prompt_yes_no(__("Create subversion directory {target} for {repo}", $__))) {
					return false;
				}
				if (!Directory::create($target)) {
					$this->error(__("Unable to create {target}", $__));
					return false;
				}
				$this->verbose_log("Created {target}", $__);
			}
			$config_dir = $app->paths->home(".subversion");
			$this->verbose_log("Subversion configuration path is {config_dir}", compact("config_dir"));
			if (!is_dir(path($target, ".svn"))) {
				if (!$this->prompt_yes_no(__("Checkout subversion {repo} to {target}", $__))) {
					return false;
				}
				$app->process->execute_arguments("svn --non-interactive --config-dir {0} co {1} {2}", array(
					$config_dir,
					$repo,
					$target
				), true);
				$this->changed = true;
				return true;
			} else {
				$results = $app->process->execute_arguments("svn --non-interactive --config-dir {0} status --show-updates {1}", array(
					$config_dir,
					$target
				));
				if (count($results) > 1) {
					$this->log($results);
					if (!$this->prompt_yes_no(__("Update subversion {target} from {repo}", $__))) {
						return false;
					}
					$app->process->execute_arguments("svn --non-interactive --config-dir {0} up --force {1}", array(
						$config_dir,
						$target
					), true);
				}
			}
			$this->changed = true;
			return true;
		} catch (Exception $e) {
			$this->error("Command failed: {e}", compact("e"));
			return false;
		}
	}
}
