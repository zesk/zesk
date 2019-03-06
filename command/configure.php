<?php

/**
 *
 */
namespace zesk;

use zesk\Configure\Engine;

/**
 * Automatically keep a series of files and directories in sync between users, with security checks
 * for superusers, and simplify version control of remote systems.
 *
 * Basically, if you deploy software to remote systems, this lets you keep your configuration files
 * in a source repository and copy them into the
 * appropriate locations without too much extra work.
 *
 * The configure command is intended to run as a mini-Zesk application and will likely include PHP configuration scripts in the future.
 *
 * @alias sync
 * @see \zesk\Configure\Engine
 * @author kent
 * @category Management
 */
class Command_Configure extends Command_Base {
	/**
	 * Append to a command to redirect stderr to stdout
	 * @var string
	 */
	const STDERR_REDIRECT = " 2>&1";

	/**
	 *
	 * @var array
	 */
	protected $option_types = array(
		"non-interactive" => "boolean",
		"environment-file" => "string",
		"host-setting-name" => "string",
	);

	/**
	 * owner - Set owner of the file (root only)
	 * mode - Set the mode of the file (root only)
	 * map - Map variables in the file using our environment before copying
	 *
	 * @var array
	 */
	protected static $file_flags = array(
		'owner' => true,
		'mode' => true,
		'map' => true,
		'trim' => true,
	);

	/**
	 *
	 * @var Engine
	 */
	private $engine = null;

	/**
	 * Whether the configuration should be saved
	 *
	 * @var boolean
	 */
	private $need_save = null;

	/**
	 *
	 * @var string
	 */
	private $host_path = null;

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

		if ($this->option("debug")) {
			$this->application->process->debug = true;
		}
		$this->engine = new Engine($this->application, $this->option());
		$this->engine->setPrompt($this);
		$this->engine->setLogger($this);

		$this->log("Configuration synchronization for: {uname}, user: {user}", $this->engine->variable_map());
		$this->determine_environment_files();
		if (!$this->determine_host_path_setting_name()) {
			return 1;
		}
		$this->determine_host_name();

		$this->save_configuration_changes();

		$this->debug_log("Variables: {variables}", array(
			"variables" => Text::format_pairs($this->engine->variable_map()),
		));
		if (!$this->configure_user()) {
			return 99;
		}
		$this->verbose_log("Success");
		return 0;
	}

	/**
	 * If the environment_file option is not set, interactively set it
	 *
	 * @return string
	 */
	private function determine_environment_file() {
		$locale = $this->application->locale;
		$value = $this->environment_file;
		$times = 0;
		$this->completions = Directory::ls("/etc/", "/(.conf|.sh|.json)$/", true);
		while (empty($value) || !is_file($value)) {
			if ($times > 2) {
				echo $locale->__("System settings file is a BASH and Zesk Configuration_Loader parsable file which contains a global which points to this host's configuration directory.\n\n");
			}
			$value = trim($this->prompt($locale->__("Path to system settings file: ")));
			++$times;
			$this->need_save = true;
		}
		$this->engine->variable_map('environment_file', $this->environment_file);
		return $this->environment_file = $value;
	}

	/**
	 * Determine the environment files for configuration
	 *
	 * @return string[]
	 */
	private function determine_environment_files() {
		$value = to_list($this->environment_files);
		if (count($value) === 0) {
			$this->environment_files = array(
				$file = $this->determine_environment_file(),
			);
			if (file_exists($app_file = $this->application->path($file))) {
				$this->environment_files[] = $app_file;
			}
		}
		return $this->environment_files;
	}

	/**
	 * If the host_setting_name option is not set, interactively set it
	 *
	 * @return NULL|string
	 */
	private function determine_host_path_setting_name() {
		$locale = $this->application->locale;
		$value = $this->host_setting_name;
		$times = 0;
		$output = false;
		while (is_array($dirs = $this->load_dirs($output)) && !array_key_exists($value, $dirs)) {
			if (count($dirs) === 0) {
				echo $locale->__("No possible directory settings in {environment_file}, please edit and add variable which points to a local directory with host information", $this->option());
			}
			if ($times > 2) {
				echo $locale->__("The system setting will point to a directory of host configurations to keep in sync.\n\n");
			}
			$this->completions = array_keys($dirs);
			$value = trim($this->prompt($locale->__("Name of system setting: ")));
			++$times;
			$this->need_save = true;
			$output = true;
		}
		$this->host_setting_name = $value;
		$this->host_path = $dirs[$value];
		$this->engine->variable_map('host_path', $this->host_path);
		if (!is_dir($this->host_path)) {
			$this->error("Host path does not exist? {host_path}", array(
				"host_path" => $this->host_path,
			));
			return null;
		}
		return $value;
	}

	/**
	 * Load a configuration file and return the loaded configuration as an array
	 *
	 * @param string $path
	 * @return array
	 */
	private function load_conf($path, $extension = null) {
		$conf = array();
		Configuration_Parser::factory($extension ? $extension : File::extension($path), File::contents($path), new Adapter_Settings_Array($conf), array(
			"lower" => false,
		))->process();
		return $conf;
	}

	/**
	 * Write out a configuration file to path
	 *
	 * @param string $path
	 * @param array $settings
	 * @return boolean
	 */
	private function save_conf($path, array $settings) {
		$conf = array();
		$contents = File::contents($path);
		$parser = Configuration_Parser::factory(File::extension($path), $contents, new Adapter_Settings_Array($conf));
		$editor = $parser->editor($contents);
		return File::put($path, $editor->edit($settings));
	}

	/**
	 * Fetch our environment file and determine which entries point to directories on this system
	 *
	 * @param string $output
	 * @return unknown[]
	 */
	private function load_dirs($output = false) {
		$locale = $this->application->locale;

		$this->verbose_log("Loading {environment_files}", array(
			"environment_files" => $this->environment_files,
		));
		$env = array();
		foreach ($this->environment_files as $environment_file) {
			$env += $this->load_conf($environment_file, File::extension($environment_file) === "sh" ? "conf" : null);
		}
		$this->engine->variable_map(array_change_key_case($env));
		$dirs = array();
		foreach ($env as $name => $value) {
			if (is_string($value) && (begins($value, "/") || begins($value, ".")) && is_dir($value)) {
				$dirs[$name] = $value;
			} else {
				$possibilities[] = $name;
			}
		}
		if ($output) {
			$this->log($locale->__("Non-directory settings: {possibilities}", array(
				"possibilities" => implode(" ", $possibilities),
			)));
			$this->log($locale->__("Available settings: {dirs}", array(
				"dirs" => implode(" ", array_keys($dirs)),
			)));
		}
		return $dirs;
	}

	/**
	 * Determine host path (an ordered list of strings) to traverse when finding inherited files
	 *
	 * @return mixed|array
	 */
	private function determine_host_name() {
		$locale = $this->application->locale;

		$this->possible_host_configurations = ArrayTools::unsuffix(Directory::ls($this->host_path), "/", true);
		$this->alias_file = path($this->host_path, "aliases.conf");
		$__ = array(
			"alias_file" => $this->alias_file,
		);
		$this->verbose_log("Alias file is {alias_file}", $__);
		$uname = $this->engine->variable_map("uname");
		if (!is_file($this->alias_file)) {
			self::file_put_contents_inherit($this->alias_file, "$uname=[]");
			$this->verbose_log("Created empty {alias_file}", $__);
		}
		$aliases = array();
		while (!is_array($host_configs = avalue($aliases = $this->load_conf($this->alias_file), strtolower($uname))) || count(array_diff($host_configs, $this->possible_host_configurations)) !== 0) {
			$configs = $this->determine_host_configurations();
			if ($this->prompt_yes_no($locale->__("Save changes to {alias_file} for $uname:{uname}? ", $__ + $this->engine->variable_map()))) {
				$this->save_conf($this->alias_file, array(
					$uname => $configs,
				));
				$this->log("Changed {alias_file}", array(
					"alias_file" => $this->alias_file,
				));
			}
		}
		$this->host_configurations = $host_configs;
		return $host_configs;
	}

	/**
	 * Interactively request a list of host configurations
	 *
	 * @return array
	 */
	private function determine_host_configurations() {
		$locale = $this->application->locale;

		$this->completions = $possible_host_configurations = $this->possible_host_configurations;
		do {
			$message = $locale->__("Host configurations: {configs}", array(
				"configs" => implode(" ", $possible_host_configurations),
			)) . "\n\n";
			$message .= $locale->__("Enter a list of configurations separated by space") . "\n";
			$host_configurations = $this->prompt("$message\n> ");
			$host_configurations = explode(" ", preg_replace("/\s+/", " ", trim($host_configurations)));
		} while (count(array_diff($host_configurations, $possible_host_configurations)) !== 0);
		$this->log("Will add host configuration for host {host}: {host_configurations}", array(
			"host" => $this->host,
			"host_configurations" => implode(" ", $host_configurations),
		));
		return $host_configurations;
	}

	/**
	 * Save configuration changes to the configuration file associated with this command
	 */
	private function save_configuration_changes() {
		if ($this->need_save) {
			$__ = array(
				"config" => $this->config,
			);
			$locale = $this->application->locale;
			if ($this->prompt_yes_no($locale->__("Save changes to {config}? ", $__))) {
				$this->save_conf($this->config, ArrayTools::kprefix($this->options_include("environment_file;host_setting_name"), __CLASS__ . "::"));
				$this->log("Wrote {config}", $__);
			}
		}
	}

	/**
	 * Configure particular user
	 *
	 * @return boolean
	 */
	private function configure_user() {
		$locale = $this->application->locale;

		$username = $this->username;
		$paths = array();
		foreach ($this->host_configurations as $host) {
			$paths[] = path($this->host_path, $host);
		}
		$this->verbose_log($locale->__("Configuration paths:\n\t{paths}", array(
			"paths" => implode("\n\t", $paths),
		)));
		$this->engine->host_paths($paths);

		$pattern = $this->option("user_configuration_file", "users/{user}/configure");
		$suffix = $this->engine->map($pattern);
		$files = File::find_all($paths, $suffix);
		$this->log($locale->__("Configuration files:\n\t{files}", array(
			"files" => implode("\n\t", $files),
		)));

		foreach ($files as $file) {
			$this->engine->variable_map(array(
				'current_host_path' => rtrim(StringTools::unsuffix($file, $suffix), "/"),
				'self_path' => dirname($file),
				'self' => $file,
			));
			$this->verbose_log("Processing file {file}", compact("file"));
			$contents = File::contents($file);
			$contents = Text::remove_line_comments($contents, "#", false);
			$lines = ArrayTools::trim_clean(explode("\n", $contents));
			foreach ($lines as $line) {
				if (!$this->engine->process($line)) {
					return false;
				}
			}
		}
		$this->engine->variable_map(array(
			'current_host_path' => null,
			'self_path' => null,
			'self' => null,
		));
		return true;
	}
}
