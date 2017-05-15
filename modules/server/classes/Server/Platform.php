<?php
/**
 * 
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
abstract class Server_Platform extends Hookable {
	
	/**
	 * 
	 * @var Application
	 */
	public $application = null;
	/**
	 * Associative array of commands to full paths
	 *
	 * @var array
	 */
	protected $shell_commands = array();
	
	/**
	 * Configuration
	 *
	 * @var Server_Configuration
	 */
	public $config = array();
	
	/**
	 * True when shell commands have to be recomputed
	 *
	 * @var boolean
	 */
	protected $_dirty_shell_commands = false;
	
	/**
	 * Group of the root user
	 */
	protected $root_group = null;
	
	/**
	 * Root user name
	 */
	protected $root_user = null;
	
	/**
	 * Host name
	 *
	 * @var string
	 */
	protected $host_name = null;
	
	/**
	 * Path to tools in the system
	 *
	 * @var string
	 */
	private $tool_path = null;
	
	/**
	 *
	 * @var array
	 */
	public $features = null;
	
	/**
	 *
	 * @var Server_Packager
	 */
	public $packager = null;
	
	/**
	 *
	 * @var Server_Files
	 */
	public $files = null;
	
	/**
	 * List of paths to search for commands
	 *
	 * @var array
	 */
	public $paths = array(
		"/bin",
		"/usr/bin"
	);
	protected $services = array();
	
	/**
	 * Construct the platform object
	 *
	 * @param string $options
	 *
	 * @throws Exception_Unimplemented
	 */
	function __construct($options = null) {
		parent::__construct($options);
		if ($this->root_group === null) {
			throw new Exception_Unimplemented("\$this->root_group is null");
		}
		if ($this->root_user === null) {
			throw new Exception_Unimplemented("\$this->root_user is null");
		}
		
		if ($this->option_bool('awareness')) {
			$this->awareness();
		}
		
		$this->host_name = $this->option("host-name", php_uname('n'));
	}
	function name() {
		return str::unprefix(get_class($this), __CLASS__);
	}
	/**
	 * Default load options for conf::load
	 *
	 * @return multitype:boolean multitype:
	 */
	private function _conf_defaults() {
		return array(
			'variables' => $this->options,
			'lower' => true,
			'trim_key' => true,
			'trim_value' => true,
			'autotype' => true,
			'overwrite' => true
		);
	}
	public function conf_load($path, $options = null) {
		$defaults = $this->_conf_defaults();
		$options = is_array($options) ? $options + $defaults : $defaults;
		$result = array();
		$array_interface = new Adapter_Settings_Array($result);
		$loader = Configuration_Parser::factory(File::extension($path), file_get_contents($path), $array_interface, $options);
		return $result;
	}
	
	/**
	 * Initialize the file system object
	 */
	private function initialize_files() {
		if ($this->files instanceof Server_Files) {
			return;
		}
		if ($this->option_bool('simulate') || !$this->is_root()) {
			$this->files = new Server_Files_Simulate($this);
		} else {
			$this->files = new Server_Files_Direct($this);
		}
		$this->application->logger->debug("File system is {class}", array(
			"class" => get_class($this->files)
		));
	}
	/**
	 * Initialize the packager mechanism
	 */
	function initialize_packager() {
		if (!$this->packager) {
			$this->packager = $this->packager();
		}
	}
	/**
	 * Initialize the configuration mechanism
	 */
	function initialize_config() {
		if ($this->config instanceof Server_Configuration) {
			return;
		}
		$server_url = $this->option('server_url');
		$host_path = $this->option('host_path');
		$default_type = !empty($server_url) ? "client" : !empty($host_path) ? 'files' : null;
		$configure_type = $this->option('configure_type', $default_type);
		$this->config = Server_Configuration::factory($configure_type, $this, $this->option());
		$this->application->logger->debug("Configuration class: {class}", array(
			"class" => get_class($this->config)
		));
		if ($this->option_bool('verbose')) {
			$this->verbose_log("Verbose mode on.");
		}
		$this->tool_path = $this->option('tool_path', '/sbin');
	}
	
	/**
	 * Create a platform object based on the system we are on
	 *
	 * @param string $options
	 *
	 * @return Server_Platform
	 */
	final static function factory($options = null) {
		$platform = php_uname('s');
		return $this->application->objects->factory(__CLASS__ . "_$platform", $options);
	}
	
	/**
	 * Test if a particular feature is available
	 *
	 * @param string $feature
	 *
	 * @return boolean
	 */
	function feature_exists($feature) {
		try {
			$class = "Server_Feature_$feature";
			return class_exists($class, true);
		} catch (Exception_Class_NotFound $e) {
			return false;
		}
	}
	
	/**
	 *
	 * @param unknown $path
	 */
	abstract public function process_is_running($path);
	/**
	 * Are we the root user?
	 *
	 * @return boolean
	 */
	abstract public function is_root();
	/**
	 *
	 * @return Server_Packager
	 */
	abstract protected function packager();
	
	/**
	 * Create a user in the operating system
	 *
	 * @param string $user
	 * @param string $group
	 * @param string $home
	 *        	Home diretory
	 * @param string $options
	 *        	Additional options for user creation
	 */
	abstract public function user_create($user, $group, $home = null, $options = null);
	
	/**
	 * Retrieve information about a particular user
	 *
	 * @param string $user
	 * @return array
	 */
	abstract public function user($user);
	
	/**
	 * Return the name of the current user
	 * @return string
	 */
	abstract public function user_current();
	
	/**
	 * Retrieve the home directory of a user
	 *
	 * @param string $user
	 * @return string
	 */
	abstract public function user_home($user);
	/**
	 * Retrieve the id of a user
	 *
	 * @param string $user
	 * @return integer
	 */
	abstract public function user_id($user);
	
	/**
	 * Retrieve the group id of a user
	 *
	 * @param string $user
	 * @return integer
	 */
	abstract public function user_group_id($user);
	
	/**
	 * Remove a user from the system
	 *
	 * @param string $user
	 * @return boolean
	 */
	abstract public function user_delete($user);
	
	/**
	 * Create a new group
	 *
	 * @param string $group
	 *        	Group to create
	 * @param mixed $members
	 *        	List or array or members
	 * @param array $options
	 *        	Additional options for group creation
	 */
	abstract public function group_create($group, $members = null, $options = null);
	
	/**
	 * Retrieve information about a particular group
	 *
	 * @param string $group
	 *        	Group to retrieve information about
	 * @return array Group information
	 */
	abstract public function group($group);
	
	/**
	 * Retrieve group id
	 *
	 * @param string $group
	 * @return integer
	 */
	abstract public function group_id($group);
	/**
	 * Retrieve group membership information
	 *
	 * @param string $group
	 * @return array list of members
	 */
	abstract public function group_members($group);
	
	/**
	 * Delete a group
	 *
	 * @param string $group
	 * @return boolean
	 */
	abstract public function group_delete($group);
	
	/**
	 * Require a particular directory to exist
	 *
	 * @param string $directory
	 * @param string $owner
	 *        	Set owner
	 * @param integer $permissions
	 *        	Set unix-style permissions
	 * @param boolean $recurse
	 *        	Create intermediate directories as well
	 * @throws Server_Exception_Permission
	 */
	public function require_directory($directory, $owner = null, $permissions = null, $recurse = true) {
		$parts = explode("/", $directory);
		$path = "";
		foreach ($parts as $part) {
			$path .= "$part/";
			if ($this->files->is_dir($path)) {
				continue;
			}
			if (!$this->files->mkdir($path)) {
				throw new Server_Exception_Permission("Can not create directory $path");
			}
			$this->owner($path, $owner, $permissions);
		}
		$this->owner($path, $owner, $permissions);
	}
	/**
	 * Validate a group name
	 *
	 * @param string $group
	 * @return boolean
	 */
	public function validate_group_name($group) {
		return preg_match('/^[a-z_][a-z0-9_-]*$/i', $group) !== 0 && strlen($group) <= 31;
	}
	
	/**
	 * The login name may be up to 31 characters long.
	 * For compatibility with
	 * legacy software, a login name should start with a letter and consist
	 * solely of letters, numbers, dashes and underscores. The login name must
	 * never begin with a hyphen (`-'); also, it is strongly suggested that
	 * neither uppercase characters nor dots (`.') be part of the name, as this
	 * tends to confuse mailers.
	 * KMD: Allow uppercase
	 *
	 * @param string $user
	 * @return boolean
	 */
	public function validate_user_name($user) {
		return preg_match('/^[a-z_][a-z0-9_-]*$/i', $user) !== 0 && strlen($user) <= 31;
	}
	
	/**
	 * Parse a user:group owner label and validate it
	 *
	 * @param string $owner
	 * @return boolean
	 */
	public function validate_owner_name($owner) {
		if (empty($owner)) {
			return false;
		}
		$user = $group = null;
		list($user, $group) = pair($owner, ":", $owner, null);
		if ($user && !$this->validate_user_name($user)) {
			return false;
		}
		if ($group && !$this->validate_group_name($group)) {
			return false;
		}
		return true;
	}
	
	/**
	 * Check if a user exists and register if it doesn't exist
	 *
	 * @param string $user
	 * @param string $group
	 * @param string $home
	 * @param string $shell
	 * @param string $uid
	 * @throws Server_Exception_Group_NotFound
	 * @return number
	 */
	function user_register($user, $group, $home = null, $shell = null, $uid = null) {
		if (!$this->group_exists($group)) {
			throw new Server_Exception_Group_NotFound($group);
		}
		if ($this->user_exists($user)) {
			return $this->user_id($user);
		}
		return $this->user_create($user, $group, $home, $shell, $uid);
	}
	
	/**
	 * Check if a group exists and create it if not found
	 *
	 * @param string $group
	 * @param string $members
	 * @param integer $gid
	 * @return number Group ID created
	 */
	function group_register($group, $members = null, $gid = null) {
		if ($this->group_exists($group)) {
			return $this->group_id($group);
		}
		return $this->user_create($group, $members, $gid);
	}
	
	/**
	 * Does a group exist?
	 *
	 * @param string $group
	 * @return boolean
	 */
	public function group_exists($group) {
		try {
			$this->group_id($group);
			return true;
		} catch (Server_Exception_Group_NotFound $e) {
			return false;
		}
	}
	
	/**
	 * Does a user exist?
	 *
	 * @param string $user
	 *
	 * @return boolean
	 */
	public function user_exists($user) {
		try {
			$this->user_id($user);
			return true;
		} catch (Server_Exception_User_NotFound $e) {
			return false;
		}
	}
	
	/**
	 * Does an owner exist
	 *
	 * @param string $owner
	 *        	Owner in the form "user:group" or "user:" or ":group"
	 * @return boolean
	 */
	final public function owner_exists($owner) {
		$user = $group = null;
		list($user, $group) = pair($owner, ":", $user, null);
		if ($user !== null && !$this->user_exists($user)) {
			return false;
		}
		if ($group !== null && !$this->group_exists($group)) {
			return false;
		}
		return true;
	}
	
	/**
	 * The unadulterated host name
	 *
	 * @return string
	 */
	public function hostname() {
		return $this->host_name;
	}
	private function awareness() {
		/**
		 * 
		 * @var \Module_AWS $aws
		 */
		$aws = $this->application->modules->object("aws");
		$awareness = $aws->awareness();
		$this->verbose_log("Using awareness");
		$this->verbose_log(Text::format_pairs($awareness));
		$user_data = avalue($awareness, 'UserData');
		$user_data = $this->conf_parse($user_data);
		$this->verbose_log("Parsed awareness data:");
		$this->verbose_log(Text::format_pairs($user_data));
		// Store into globals
		$this->application->configuration->paths_set($user_data);
		// Then copy into here
		$this->inherit_global_options();
	}
	
	/**
	 * Configure the platform
	 */
	final public function configure(array $features = null) {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		$this->initialize_packager();
		$this->initialize_config();
		$this->initialize_files();
		
		$this->packager->configure();
		
		$this->call_hook("configure_features");
		$feature_list = $this->config->feature_list();
		$this->application->logger->debug("Feature list is {features}", array(
			"features" => implode(", ", $feature_list)
		));
		foreach ($feature_list as $feature_name) {
			$this->features[$feature_name] = $zesk->objects->factory("Server_Feature_$feature_name", $this);
		}
		foreach ($this->features as $feature_name => $feature) {
			/* @var $feature Server_Feature */
			$this->verbose_log("$feature_name preconfigure ...");
			$feature->preconfigure();
			$dependencies = $feature->dependencies();
		}
		$this->verbose_log("All services preconfigured");
	}
	/**
	 *
	 * @param string $name
	 * @return Server_Feature
	 */
	final public function feature($name) {
		return avalue($this->features, strtolower($name), null);
	}
	/**
	 * Prompt user
	 *
	 * @param string $message
	 *
	 * @return boolean
	 */
	public function confirm($message) {
		do {
			echo $message . " (Y/n)? ";
			$reply = strtolower(trim(fgets(STDIN)));
			if ($reply === "y" || $reply === "") {
				return true;
			}
			if ($reply === "n") {
				return false;
			}
		} while (true);
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
	final function install_tool($tool, $installed_name = null) {
		if ($installed_name === null) {
			$installed_name = basename($tool);
		}
		$dst = path($this->tool_path, $installed_name);
		$this->update($tool, $dst);
		return $dst;
	}
	final public function package_install($package) {
		$package = to_list($package);
		foreach ($package as $p) {
			$this->verbose_log("Installing package $p");
			$this->packager->install($p);
		}
		return $this;
	}
	final public function package_installed($package) {
		$package = to_list($package);
		$result = array();
		foreach ($package as $p) {
			if (!$this->packager->package_installed($p)) {
				$result[] = $p;
			}
		}
		return count($result) === 0 ? true : $result;
	}
	/**
	 * Shell command formatting
	 *
	 * @param string $command
	 * @param array $args
	 *        	Positional arguments
	 * @return string
	 */
	private function _format_command($command, array $args) {
		foreach ($args as $i => $arg) {
			$args[$i] = escapeshellarg($arg);
		}
		return map($command, $args);
	}
	
	/**
	 * Shell command formatting (variable arguments)
	 *
	 * Usage:
	 * <code>
	 * $platform->format_comand("ls -l {0}", $filename);
	 * </code>
	 *
	 * @param string $command
	 * @return string
	 */
	final function format_command($command) {
		$args = func_get_args();
		array_shift($args);
		return $this->_format_command($command, $args);
	}
	
	/**
	 * Run a shell command and return the first line returned.
	 * Arguments passed as parameters.
	 *
	 * @param string $command
	 * @throws Server_Exception_Command
	 * @return string
	 */
	final function exec_one($command) {
		$args = func_get_args();
		array_shift($args);
		$result = $this->exec_array($command, $args);
		return avalue($result, 0, "");
	}
	
	/**
	 * Run a shell command and return the first line returned.
	 * Arguments pased as an array.
	 *
	 * @param string $command
	 * @param array $arguments
	 * @throws Server_Exception_Command
	 * @return string
	 */
	final function exec_one_array($command, array $arguments) {
		$result = $this->exec_array($command, $arguments);
		return avalue($result, 0, "");
	}
	
	/**
	 * Run a shell command and return output.
	 * Arguments passed as parameters.
	 *
	 * @param string $command
	 * @throws Server_Exception_Command
	 * @return string
	 */
	final function exec($command) {
		$args = func_get_args();
		array_shift($args);
		return $this->exec_array($command, $args);
	}
	
	/**
	 * Run a shell command and return output.
	 * Arguments passed as array.
	 *
	 * @param unknown $command
	 * @param array $arguments
	 * @throws Server_Exception_Command
	 * @return NULL
	 */
	final function exec_array($command, array $arguments) {
		$command = $this->_format_command($command, $arguments);
		$this->verbose_log("Server_Platform::exec " . $command);
		$return = $result = null;
		exec($command, $result, $return);
		if (intval($return) === 0) {
			return $result;
		}
		throw new Server_Exception_Command($return, $result);
	}
	
	/**
	 * Test if a shell command exists on the system, optionally testing it
	 *
	 * @param string $command
	 *        	Binary shell command to run
	 * @return string Full path of command of success, or false
	 */
	public function has_shell_command($command) {
		$command = strval(trim($command));
		if ($this->_dirty_shell_commands) {
			$this->shell_commands = array();
			$this->_dirty_shell_commands = false;
		}
		if (array_key_exists($command, $this->shell_commands)) {
			return $this->shell_commands[$command];
		}
		foreach ($this->paths as $path) {
			$alias = path($path, $command);
			if (is_executable($alias)) {
				$this->shell_commands[$command] = $alias;
				return true;
			} else if (is_file($alias)) {
				$this->warning("Shell command $command exists at $alias but is not executable");
			}
		}
		return false;
	}
	
	/**
	 * Set owner of a file in the system
	 *
	 * @param string $path
	 * @param string $owner
	 * @param string $permissions
	 * @throws Server_Exception_Permission
	 * @throws Exception_File_NotFound
	 */
	public function owner($path, $owner = null, $permissions = null) {
		$uid = $gid = null;
		$group = null;
		if ($owner !== null) {
			list($user, $group) = pair($owner, ":", $owner, null);
			$uid = is_string($user) ? $this->user_id($user) : intval($user);
		}
		if ($group !== null) {
			$gid = is_string($group) ? $this->group_id($group) : intval($group);
		} else {
			$gid = $this->user_group_id($uid);
		}
		$result = $this->files->stat($path);
		$options = $result['perms'];
		if (is_int($permissions)) {
			if ($permissions !== $options['mode']) {
				if (!$this->files->chmod($path, $permissions)) {
					throw new Server_Exception_Permission("Unable to change mode on $path to $permissions (" . file::mode_to_string($permissions) . ")");
				}
			}
		} else if (is_string($permissions)) {
			if ($permissions !== $options['string']) {
				$options_int = file::string_to_mode($permissions);
				if (!@chmod($path, $options_int)) {
					throw new Server_Exception_Permission("Unable to change mode on $path to $permissions ($options_int)");
				}
			}
		}
		$owner = $result['owner'];
		if ($result === false) {
			throw new Exception_File_NotFound($path);
		}
		$fuid = intval(avalue($owner, 'uid'));
		$fgid = intval(avalue($owner, 'gid'));
		if ($fuid !== $uid || $fgid !== $gid) {
			$this->root_exec("chown $uid:$gid {0}", $path);
		}
	}
	/**
	 * Apply permissions to a file based on the options
	 *
	 * @param string $path
	 * @param array $options
	 * @return boolean
	 */
	protected function apply_permissions($path, array $options = array()) {
		$owner = avalue($options, 'owner');
		if ($owner === null) {
			$user = avalue($options, 'user', '');
			$group = avalue($options, 'group', '');
			$owner = "$user:$group";
			if ($owner === ":") {
				$owner = null;
			}
		}
		$mode = avalue($options, 'mode');
		if ($mode === null && $owner === null) {
			return true;
		}
		return $this->owner($path, $owner, $mode);
	}
	
	/**
	 * Replace a file in the system with contents of a string
	 *
	 * @param string $source_contents
	 * @param string $dest
	 *        	Path to destination
	 * @param array $options
	 *        	Destination file options
	 * @throws Server_Exception_Permission
	 * @return Server_Platform
	 */
	protected function replace_file_contents($source_contents, $dest, array $options = array()) {
		if (!$this->files->file_put_contents($dest, $source_contents)) {
			throw new Server_Exception_Permission("Write to file $dest");
		}
		$this->apply_permissions($dest, $options);
		return $this;
	}
	
	/**
	 * Replace a file in the system with another file
	 *
	 * @param string $source_file
	 *        	Source file name
	 * @param string $dest
	 *        	Destination file name
	 * @param array $options
	 *        	Destination file options
	 * @throws Server_Exception_Permission
	 * @return Server_Platform
	 */
	protected function replace_file($source_file, $dest, array $options = array()) {
		if (!$this->files->copy($source_file, $dest)) {
			throw new Server_Exception_Permission("Read from $source_file, write to $dest");
		}
		$this->apply_permissions($dest, $options);
		return $this;
	}
	
	/**
	 * Map contents of a string with the configuration variables
	 *
	 * @param string $contents
	 * @param mixed $map
	 * @return string
	 */
	private function _map($contents, $map) {
		if ($map === true) {
			$map = $this->_default_map();
		}
		if (is_array($map)) {
			return map($contents, $map);
		}
		return $contents;
	}
	
	/**
	 * Return default map
	 *
	 * @return array
	 */
	private function _default_map() {
		$variables = $this->config->variables();
		return $variables;
	}
	
	/**
	 *
	 * @param string $source
	 * @param string $dest
	 * @param array $options
	 * @throws Server_Exception_Permission
	 * @return boolean
	 */
	private function _update_file($source, $dest, array $options = array()) {
		if (is_dir($dest)) {
			$dest = path($dest, basename($source));
		}
		$source_contents = null;
		$map = avalue($options, 'map');
		if ($map === true) {
			$map = $this->config->variables();
		}
		if ($map) {
			$source_contents = $this->files->file_get_contents($source);
			if ($source_contents === false) {
				throw new Server_Exception_Permission("Read from $source");
			}
			$source_contents = $this->_map($source_contents, $map);
			$source_checksum = md5($source_contents);
			if (!$this->files->file_exists($dest) || $source_checksum !== $this->files->md5_file($dest)) {
				$this->verbose_log("Writing (map) $source -> $dest");
				$this->replace_file_contents($source_contents, $dest, $options);
				return true;
			}
		} else {
			$source_checksum = $this->files->md5_file($source);
			if (!file_exists($dest) || $source_checksum !== md5_file($dest)) {
				$this->verbose_log("Writing $source -> $dest");
				$this->replace_file($source, $dest, $options);
				return true;
			}
		}
		$this->verbose_log("Unchanged $source -> $dest");
		return false;
	}
	private function _update_dirs($source, $dest, array $options = array()) {
	}
	public function update($source, $dest, array $options = array()) {
		if ($this->files->is_file($source)) {
			if ($this->files->is_file($dest)) {
				return $this->_update_file($source, $dest, $options);
			} else if (is_dir($dest)) {
				return $this->_update_file($source, path($dest, basename($source)), $options);
			} else if (is_dir(dirname($dest))) {
				// File does not exist?
				return $this->_update_file($source, $dest, $options);
			} else {
				throw new Exception_File_NotFound($dest);
			}
		} else if ($this->files->is_dir($source)) {
			if ($this->files->is_file($dest)) {
				throw new Exception_Semantics("$source directory to file $dest ... unsure what to do");
			} else if ($this->files->is_dir($dest)) {
				return $this->_update_dirs($source, $dest, $options);
			} else {
				throw new Exception_File_NotFound($dest);
			}
		} else {
			throw new Exception_File_NotFound($source);
		}
	}
	public function update_catenate($relative_file, array $paths, $dest, array $options = array()) {
		$files = array();
		$contents = "";
		foreach ($paths as $path) {
			$file = path($path, $relative_file);
			if ($this->files->is_file($file)) {
				$files[] = $file;
				$source = file_get_contents($file);
				if ($source === false) {
					throw new Server_Exception_Permission("Read $source");
				}
				$contents .= $source;
			}
		}
		if (count($files) !== 0) {
			throw new Exception_File_NotFound("$relative_file => $dest");
		}
		$map = avalue($options, 'map');
		if ($map)
			if (is_array($map)) {
				$contents = map($contents, $map);
			}
		$checksum = md5($contents);
		$copy = false;
		if (!$this->files->is_file($dest)) {
			$copy = true;
		} else if ($this->files->md5_file($dest) !== $checksum) {
			$copy = true;
		}
		if (!$copy) {
			$this->verbose_log("update_catenate $relative_file unchanged to $dest");
		}
		return $this->replace_file_contents($contents, $dest, $options);
	}
	abstract function login_script_install($user, $name, $command);
	abstract function login_script_installed($user, $name);
	abstract function login_script_run($user, $name);
	abstract function login_script_uninstall($user, $name);
	public function restart_service($name) {
		$this->root_exec("svc -t /service/$name");
	}
	public function verbose_log($message, array $args = array()) {
		if ($this->option_bool('verbose')) {
			$this->application->logger->debug($message, $args);
		}
	}
	public function warning($message, array $args = array()) {
		$this->application->logger->warning($message, $args);
	}
	public function error($message, array $args = array()) {
		$this->application->logger->error($message, $args);
	}
	public function notice($message, array $args = array()) {
		$this->application->logger->notice($message, $args);
	}
	/**
	 * Copy configuration files
	 *
	 * @param string $type
	 * @param mixed $files
	 * @param string $dest
	 * @param array $options
	 * @return boolean changed
	 */
	final public function configuration_files($type, $files, $dest, array $options = array()) {
		$changed = false;
		$updates = $this->config->configuration_files($type, $files, $dest, $options);
		foreach ($updates as $update) {
			list($usource, $udest, $uoptions) = $update;
			if (!is_array($uoptions)) {
				$uoptions = array();
			}
			if ($this->update($usource, $udest, $uoptions)) {
				$changed = true;
			}
		}
		return $changed;
	}
	final public function database_preconfigure($urls) {
		// TODO
		$urls = to_list($urls);
		foreach ($urls as $url) {
			try {
				$db = app()->database_factory($url);
			} catch (Database_Exception $e) {
				$this->application->logger->error("Need to configure DB_URL {url}: Reason {error}", array(
					"url" => URL::remove_password($url),
					"error" => $e->getMesage()
				));
			}
		}
	}
	final public function host_aliases() {
		$this->initialize();
		return $this->config->host_aliases();
	}
	
	/**
	 * Retrieve the volume short name given the volume's path
	 *
	 * @param string $path
	 * @return string
	 */
	function volume_short_name($path) {
		return basename($path);
	}
}
