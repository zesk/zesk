<?php
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
	function __construct(Server_Platform $platform) {
		parent::__construct($platform);
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
			throw new Exception_Authentication("Must be logged in as root");
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
			throw new Exception_Authentication("Must not be logged in as root");
		}
		return $this;
	}
	public final function root_exec($command) {
		return $this->require_root()->exec($command);
	}
	public final function root_exec_array($command, array $arguments) {
		return $this->require_root()->exec_array($command, $arguments);
	}
	public final function install_tool($tool, $installed_name = null) {
		return $this->platform->install_tool($tool, $installed_name);
	}
	public final function exec($command) {
		$arguments = func_get_args();
		return call_user_func_array(array(
			$this->platform,
			"exec"
		), $arguments);
	}
	public final function exec_array($command, array $arguments) {
		array_unshift($arguments, $command);
		return call_user_func_array(array(
			$this->platform,
			"exec"
		), $arguments);
	}
	
	/**
	 * Test if a shell command exists on the system, optionally testing it
	 *
	 * @param string $command        	
	 * @return string error message, or true if succeeded
	 */
	public final function has_shell_command($command) {
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
	public final function is_root() {
		return $this->platform->is_root();
	}
	public final function group_id($group_name) {
		return $this->platform->group_id($group_name);
	}
	public final function user_id($user_name) {
		return $this->platform->user_id($user_name);
	}
	public final function user_group_id($user_name) {
		return $this->platform->user_group_id($user_name);
	}
	public final function restart_service($name) {
		return $this->platform->restart_service($name);
	}
	public final function confirm($message) {
		return $this->platform->confirm($message);
	}
	public final function package_install($package) {
		return $this->platform->install($package);
	}
	public final function package_installed($package) {
		return $this->platform->installed($package);
	}
	public final function path($name, $set = null) {
		return $this->config->path($name, $set);
	}
	public final function path_list($name, $set = null) {
		return $this->config->path_list($name, $set);
	}
	public final function file($name, $set = null) {
		return $this->config->file($name, $set);
	}
	public final function executable($name, $set = null) {
		return $this->config->executable($name, $set);
	}
	public final function package($name, $set = null) {
		return $this->config->package($name, $set);
	}
	public final function package_list($name, $set = null) {
		return $this->config->package_list($name, $set);
	}
	public final function require_directory($path, $user = null, $mode = null) {
		return $this->platform->require_directory($path, $user, $mode);
	}
	public final function generate_file($package, $file) {
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