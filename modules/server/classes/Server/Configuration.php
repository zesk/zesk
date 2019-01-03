<?php

/**
 *
 */
namespace zesk;

/**
 * Array of settings and values used to configure this system
 */
abstract class Server_Configuration extends Hookable {
	/**
	 *
	 * @var Server_Platform
	 */
	protected $platform = null;

	/**
	 * Array of settings and types
	 *
	 * Valid types are:
	 *
	 * path
	 * path list
	 * file
	 * file list
	 * package
	 * package list
	 * feature
	 * feature list
	 * executable
	 * user
	 * group
	 * owner
	 *
	 * @var array
	 */
	protected $variable_types = array();

	/**
	 * Array of lowercase name => display case name
	 *
	 * @var array
	 */
	protected $variable_types_display = array();

	public static function factory($type, Server_Platform $platform, $options = null) {
		if (empty($type)) {
			throw new Exception_Parameter("No Configuration type passed to {class}::factory", array(
				"class" => __CLASS__,
			));
		}
		$class = "Server_Configuration_$type";
		return $platform->application->factory($class, $platform, $options);
	}

	public function __construct(Server_Platform $platform, $options = null) {
		$this->platform = $platform;
		parent::__construct($platform->application, $options);
		$this->inherit_global_options();
	}

	final public function verbose_log($message, array $args = array()) {
		return $this->platform->verbose_log($message, $args);
	}

	final public function variables() {
		return $this->options;
	}

	private function validate_type_before($type, $name, $value) {
		if (ends($type, " list")) {
			if (!is_array($value)) {
				throw new Exception_Semantics("Setting $name of type $type should be an array: " . gettype($value));
			}
			$simple_type = substr($type, 0, -5);
			foreach ($value as $val) {
				if (!$this->_validate_type_before($simple_type, $name, $val)) {
					throw new Server_Exception("Invaid simple type $simple_type value $val in setting $name");
				}
			}
			return true;
		}
		return $this->_validate_type_before($type, $name, $value);
	}

	private function validate_type_after($type, $name, $value) {
		if (StringTools::ends($type, " list")) {
			if (!is_array($value)) {
				throw new Exception_Semantics("Setting $name of type $type should be an array: " . gettype($value));
			}
			$simple_type = substr($type, 0, -5);
			foreach ($value as $val) {
				if (!$this->validate_type_simple($simple_type, $name, $val)) {
					throw new Server_Exception("Invaid simple type $simple_type value $val in setting $name");
				}
			}
			return true;
		}
		return $this->_validate_type_after($type, $name, $value);
	}

	private function _validate_type_before($type, $name, $value) {
		switch ($type) {
			case "path":
				return File::path_check($value) !== false;
			case "file":
				return File::path_check($value) !== false;
			case "package":
				return $this->packager->package_exists($value);
			case "feature":
				return $this->platform->feature_exists($name);
			case "executable":
				return File::path_check($value) !== false;
			case "user":
				return $this->platform->validate_user_name($value);
			case "group":
				return $this->platform->validate_group_name($value);
			case "owner":
				return $this->platform->validate_owner_name($value);
			default:
				throw new Exception_Semantics("Unknown type \"$type\" supplied for name $name");
		}
	}

	private function _validate_type_after($type, $name, $value) {
		switch ($type) {
			case "path":
				return is_string($value) && is_dir($value);
			case "file":
				return is_string($value) && is_file($value);
			case "package":
				return $this->packager->package_installed($name);
			case "feature":
				return $this->platform->feature_installed($name);
			case "executable":
				return is_string($value) && is_executable($value);
			case "user":
				return $this->platform->user_exists($name);
			case "group":
				return $this->platform->group_exists($name);
			case "owner":
				return $this->platform->owner_exists($name);
			default:
				throw new Exception_Semantics("Unknown type \"$type\" supplied for name $name");
		}
	}

	final public function service_path($add = null) {
		if ($add !== null) {
			if (!is_dir($add)) {
				throw new Exception_Directory_NotFound($add);
			}
			$this->service_path[] = $add;
			return $this;
		}
		return $this->service_path;
	}

	/**
	 * Register global settings associated with a system configuration
	 *
	 * @param array $variable_types
	 *        	Array of setting name => type name
	 * @param array $variable_defaults
	 *        	Array of setting name => default value
	 * @param boolean $check_conflict
	 *        	Check whether all settings have been registered previously
	 * @return Server_Configuration
	 * @throws Exception_Key
	 */
	final public function register_types(array $variable_types, array $variable_defaults, $check_conflict = true) {
		foreach ($variable_types as $name => $type) {
			$lowname = self::_option_key($name);
			$this->variable_types_display[$lowname] = $name;
			if ($check_conflict && ($existing_type = avalue($this->variable_types, $name, $type)) !== $type) {
				throw new Exception_Key("Conflicting configuration type for $name: $existing_type !== $type");
			}
			$this->variable_types[$lowname] = $type;
		}
		return $this;
	}

	/**
	 * Validate a type before it has been installed/created/etc.
	 *
	 * @param string $name
	 *        	Name of setting
	 * @return boolean Whether it the setting is syntactically correct
	 */
	final public function check_type_before($name) {
		try {
			return $this->validate_type_before(avalue($this->variable_types, strtolower($name)), $name, $this->option($name, $this->application->configuration->get($name)));
		} catch (Exception $e) {
			return false;
		}
	}

	/**
	 * Validate a type after it has been installed/created/etc.
	 *
	 * @param string $name
	 *        	Name of setting
	 * @return boolean Whether it has been installed and validated
	 */
	final public function check_type_after($name) {
		/* @var $zesk zesk\Kernel */
		try {
			return $this->validate_type_after(avalue($this->variable_types, strtolower($name)), $name, $this->option($name, $this->application->configuration->get($name)));
		} catch (Exception $e) {
			return false;
		}
	}

	/**
	 * Retrieve settings
	 *
	 * @param string $url
	 *        	To retrieve
	 * @return string $path Of retrieved file
	 */
	abstract public function remote_package($url);

	/**
	 * Return an array of hostname => aliasname
	 */
	public function host_aliases() {
		return array();
	}

	/**
	 *
	 * @param unknown $type
	 * @param unknown $files
	 * @param unknown $dest
	 * @param array $options
	 */
	abstract public function configuration_files($type, $files, $dest, array $options = array());

	/**
	 *
	 * @param Server_Feature $feature
	 */
	abstract public function configure_feature(Server_Feature $feature);

	/**
	 * Retrieve directory to configure a feature
	 *
	 * @param string $type
	 *        	Feature to retrieve configuration files for
	 * @return string $path Of retrieved file
	 */
	public function feature_directory($type, $map = true) {
		throw new Exception_Unimplemented();
	}

	public function path($name, $set = null) {
		return $this->variable($name, "path", $set);
	}

	public function executable($name, $set = null) {
		return $this->variable($name, "executable", $set);
	}

	public function feature_dependency_list($name, $set = null) {
		return $this->variable($name, "feature", $set);
	}

	public function package_dependency_list($name, $set = null) {
		return $this->variable($name, "package list", $set);
	}

	public function type_list($name, $type, $set = null) {
		return $this->variable($name, "$type list", $set);
	}

	public function user_list($name, $set = null) {
		return $this->type_list($name, "user", "list", $set);
	}

	public function variable($name, $type, $set = null) {
		$k = self::_option_key($name);
		$value = apath($this->options, $k, null, "::");
		$registered_type = apath($this->variable_types, $k, null, "::");
		assert($registered_type === $type);
		if ($set !== null) {
			apath_set($this->options, $k, $set);
			return true;
		} else {
			return avalue($this->options, $k);
		}
	}
}
