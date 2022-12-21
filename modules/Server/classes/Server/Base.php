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
class Server_Base extends Hookable {
	/**
	 *
	 * @var Server_Platform
	 */
	protected $platform = null;

	/**
	 *
	 * @var Server_Configuration
	 */
	protected $config = null;

	/**
	 * Create a new Server Base
	 *
	 * @param Server_Platform $platform
	 */
	public function __construct(Server_Platform $platform) {
		parent::__construct($platform->application);

		$this->platform = $platform;
		$this->config = $platform->config;
	}

	/**
	 * If not root, throw an error
	 *
	 * @return Server_Base
	 */
	protected function require_root() {
		if (!$this->is_root()) {
			throw new Exception_Authentication('Must be logged in as root');
		}
		return $this;
	}

	/**
	 * If root, throw an error
	 *
	 * @return Server_Base
	 */
	protected function not_root() {
		if ($this->is_root()) {
			throw new Exception_Authentication('Must not be logged in as root');
		}
		return $this;
	}

	final public function root_exec($command) {
		return $this->require_root()->exec($command);
	}

	final public function root_exec_array($command, array $arguments) {
		return $this->require_root()->exec_array($command, $arguments);
	}

	final public function install_tool($tool, $installed_name = null) {
		return $this->platform->install_tool($tool, $installed_name);
	}

	final public function exec($command) {
		$arguments = func_get_args();
		return call_user_func_array([
			$this->platform,
			'exec',
		], $arguments);
	}

	final public function exec_array($command, array $arguments) {
		array_unshift($arguments, $command);
		return call_user_func_array([
			$this->platform,
			'exec',
		], $arguments);
	}

	/**
	 * Test if a shell command exists on the system, optionally testing it
	 *
	 * @param string $command
	 * @return string error message, or true if succeeded
	 */
	final public function has_shell_command($command) {
		return $this->platform->has_shell_command($command);
	}

	public function owner($path, $user = null, $permissions = null) {
		return $this->platform->owner($path, $user, $permissions);
	}

	public function update($source, $dest, $map = false) {
		return $this->platform->update($source, $dest, $map);
	}

	public function update_catenate($filename, array $paths, $dest, $map = false) {
		return $this->platform->update_catenate($filename, $paths, $dest, $map);
	}

	/**
	 *
	 * @return boolean
	 */
	final public function is_root() {
		return $this->platform->is_root();
	}

	final public function group_id($group_name) {
		return $this->platform->group_id($group_name);
	}

	final public function user_id($user_name) {
		return $this->platform->user_id($user_name);
	}

	final public function user_group_id($user_name) {
		return $this->platform->user_group_id($user_name);
	}

	final public function restart_service($name) {
		return $this->platform->restart_service($name);
	}

	final public function confirm($message) {
		return $this->platform->confirm($message);
	}

	final public function package_install($package) {
		return $this->platform->install($package);
	}

	final public function package_installed($package) {
		return $this->platform->installed($package);
	}

	final public function path($name, $set = null) {
		return $this->config->path($name, $set);
	}

	final public function path_list($name, $set = null) {
		return $this->config->path_list($name, $set);
	}

	final public function file($name, $set = null) {
		return $this->config->file($name, $set);
	}

	final public function executable($name, $set = null) {
		return $this->config->executable($name, $set);
	}

	final public function package($name, $set = null) {
		return $this->config->package($name, $set);
	}

	final public function package_list($name, $set = null) {
		return $this->config->package_list($name, $set);
	}

	final public function require_directory($path, $user = null, $mode = null) {
		return $this->platform->require_directory($path, $user, $mode);
	}

	final public function generate_file($package, $file) {
		return $this->config->generate_file($package, $file);
		// if ($paths === null) {
		// $paths = $this->service_path();
		// }
		// foreach ($paths as $path) {
		// $path = path($path, $file);
		// if (file_exists($path)) {
		// return $path;
		// }
		// }
		// return null;
	}
}
