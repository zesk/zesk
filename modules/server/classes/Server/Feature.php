<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
abstract class Server_Feature extends Server_Base {
	/**
	 * Short name for this feature
	 *
	 * @var string
	 */
	public $code = null;

	/**
	 * List of platforms supported for this feature.
	 * Null means all platforms.
	 *
	 * @var array
	 */
	protected $platforms = null;

	/**
	 * List of required shell commands to execute
	 *
	 * @var array
	 */
	protected $commands = [];

	/**
	 * List of required packages for this feature
	 *
	 * @var array
	 */
	protected $packages = [];

	/**
	 * List of required features to install this one
	 *
	 * @var array
	 */
	protected $dependencies = [];

	/**
	 * List of settings with types which must be set to configure
	 *
	 * @var array
	 */
	protected $settings = [];

	/**
	 * The directory of this feature's root for configuration in the source tree
	 *
	 * @var string
	 */
	protected $configure_root = null;

	/**
	 * List of paths where commands are found for this feature to be installed.
	 *
	 * @var array
	 */
	// protected $command_paths = array();
	public function __construct(Server_Platform $platform) {
		parent::__construct($platform);

		$this->code = strtolower(StringTools::unsuffix(get_class($this), __CLASS__));
		if ($this->configure_root === null) {
			$class = $this->application->autoloader->search($class, [
				"inc",
			]);
			$this->configure_root = StringTools::unsuffix($class, ".inc");
		}

		$this->call_hook("construct");

		$this->commands = to_list($this->commands, []);
		$this->packages = to_list($this->packages, []);
		$this->dependencies = to_list($this->dependencies, []);
		$this->settings = to_array($this->settings, []);
		$this->defaults = to_array($this->defaults, []);

		$platform_name = strtolower($this->platform->name());
		$platform_conf_file = path($this->configure_root, "platform", $platform_name . ".conf");
		if (is_file($platform_conf_file)) {
			$this->application->logger->notice("Loading feature default configuration file {file} for {class}", [
				"file" => $platform_conf_file,
				"class" => get_class($this),
			]);
			$this->defaults = $this->platform->conf_load($platform_conf_file) + $this->defaults;
		}

		$this->config->register_types(ArrayTools::kprefix($this->settings, $this->code . '::'), ArrayTools::kprefix($this->defaults, $this->code));

		// $this->platform->register_path($this->command_paths);

		$this->config->configure_feature($this);

		$this->initialize();
	}

	public function configure_path() {
		return $this->configure_root;
	}

	public function installable() {
		$errors = [];
		foreach ($this->settings as $name => $type) {
			if (!$this->config->check_type_before($name)) {
				ArrayTools::append($errors, "configuration", $name);
			}
		}
		foreach ($this->commands as $command) {
			if (!$this->has_shell_command($command)) {
				ArrayTools::append($errors, "commands", $command);
			}
		}
		foreach ($this->packages as $package) {
			if (!$this->platform->package_exists($package)) {
				ArrayTools::append($errors, "packages", $package);
			}
		}
		foreach ($this->dependencies as $feature) {
			if (!$this->feature_exists($feature)) {
				ArrayTools::append($errors, "features", $feature);
			}
		}
		return $errors;
	}

	/**
	 */
	public function install_check() {
		$errors = $this->installable();
		foreach ($this->settings as $name => $type) {
			if (!$this->config->check_type_after($name)) {
				ArrayTools::append($errors, "configuration", $name);
			}
		}
		return $errors;
	}

	public function initialize(): void {
	}

	public function preconfigure(): void {
	}

	public function install(): void {
		static $installing = false;
		if ($installing) {
			return;
		}
		$installing = true;
		$this->feature_install($this->features);
		$this->package_install($this->packages);
		$this->call_hook("install");
		$installing = false;
	}

	public function configure(): void {
	}

	/**
	 *
	 * @return array
	 */
	final public function commands() {
		return $this->commands;
	}

	/**
	 *
	 * @return array
	 */
	final public function dependencies() {
		return $this->dependencies;
	}

	/**
	 *
	 * @return array
	 */
	final public function settings() {
		return $this->settings;
	}

	/**
	 * Retrieve a remote package for installation and place it in a temporary directory
	 *
	 * @param unknown_type $url
	 */
	final public function remote_package($url) {
		return $this->config->remote_package($url);
	}

	final public function configuration_files($type, $files, $dest, array $options = []) {
		$options['+search_path'] = path($this->configure_root, 'files');
		return $this->platform->configuration_files($type, $files, $dest, $options);
	}

	final public function database_preconfigure($urls) {
		$urls = to_list($urls);
		return $this->platform->database_preconfigure($urls);
	}
}
