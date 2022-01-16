<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage configuration
 * @author kent
 * @copyright &copy; 2019 Market Acumen, Inc.
 */
namespace zesk;

/**
 * @author kent
 */
namespace zesk\Configure;

use zesk\StringTools;
use zesk\ArrayTools;
use zesk\File;
use zesk\Application;
use zesk\Hookable;
use zesk\System;
use zesk\Text;
use zesk\PHP;
use zesk\Directory;
use zesk\Exception_Command;
use zesk\JSON;
use zesk\Logger\Handler;
use zesk\Interface_Prompt;

/**
 * Given a set of commands, configure an environment
 *
 * @author kent
 *
 */
class Engine extends Hookable {
	/**
	 * Append to a command to redirect stderr to stdout
	 * @var string
	 */
	public const STDERR_REDIRECT = " 2>&1";

	/**
	 *
	 * @var array
	 */
	protected array $option_types = [
		"non-interactive" => "boolean",
		"environment-file" => "string",
		"host-setting-name" => "string",
	];

	/**
	 * owner - Set owner of the file (root only)
	 * mode - Set the mode of the file (root only)
	 * map - Map variables in the file using our environment before copying
	 *
	 * @var array
	 */
	protected static $file_flags = [
		'owner' => true,
		'mode' => true,
		'map' => true,
		'trim' => true,
	];

	/**
	 * @var Interface_Prompt
	 */
	private $prompt = null;

	/**
	 * Whether the configuration was changed
	 *
	 * @var boolean
	 */
	private $changed = null;

	/**
	 * Whether the configuration should be saved
	 *
	 * @var boolean
	 */
	private $need_save = null;

	/**
	 * Whether anything was skipped (out of sync)
	 *
	 * @var integer
	 */
	private $incomplete = 0;

	/**
	 * List of host paths for this host
	 *
	 * @var array
	 */
	private $host_paths = [];

	/**
	 * Variables to map when copying files around, etc.
	 *
	 * @var array
	 */
	private $variable_map = [];

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
	public function __construct(Application $application, array $options = []) {
		parent::__construct($application, $options);

		$this->variable_map['home'] = $this->application->paths->home();
		$this->variable_map['uname'] = $uname = System::uname();
		$this->variable_map['low_uname'] = strtolower($uname);

		$this->variable_map['user'] = $username = avalue($_SERVER, 'USER');
		$this->variable_map['username'] = $username; // Deprecate?

		$this->variable_map['application_root'] = $this->application->path();
		$this->variable_map['application_home'] = $this->application->path();
		$this->variable_map['zesk_home'] = $this->application->zesk_home();

		/* @deprecated 2018-01 */
		$this->variable_map['zesk_root'] = $this->application->zesk_home();
		$this->variable_map['zesk_application_root'] = $this->application->path(); // Deprecated

		$this->incomplete = 0;
		$this->debug_log("Variables: {variables}", [
			"variables" => Text::format_pairs($this->variable_map),
		]);

		[$this->current_uid, $this->current_gid] = $this->current_uid_gid();
	}

	/**
	 *
	 * @param Interface_Prompt $prompt
	 * @return \zesk\Configure\Engine
	 */
	public function setPrompt(Interface_Prompt $prompt) {
		$this->prompt = $prompt;
		return $this;
	}

	/**
	 *
	 * @param callable $prompt
	 * @return \zesk\Configure\Engine
	 */
	public function setLogger(Handler $handler) {
		$this->logger = $handler;
		return $this;
	}

	/**
	 * Getter/setter for host paths
	 *
	 * @param array $paths
	 * @return array|\zesk\Configure\Engine
	 */
	public function host_paths(array $paths = null) {
		if ($paths === null) {
			return $this->host_paths;
		}
		$this->host_paths = $paths;
		return $this;
	}

	/**
	 * Pass log message to logger
	 *
	 * @param string $message
	 * @param array $arguments
	 */
	public function log($message, array $arguments = []): void {
		if ($this->logger) {
			$this->logger->log($message, $arguments);
		}
	}

	/**
	 *
	 * @param string $message
	 * @param array $arguments
	 */
	public function error($message, array $arguments = []): void {
		if (!$message) {
			return;
		}
		$this->log($message, [
			"severity" => "error",
		] + $arguments);
	}

	/**
	 * Add logging support
	 *
	 * @param string $message
	 * @param array $arguments
	 */
	public function verbose_log($message, array $arguments = []): void {
		if ($this->optionBool('verbose')) {
			$this->log($message, $arguments);
		}
	}

	/**
	 * @param string $message
	 * @param array $arguments
	 */
	public function debug_log($message, array $arguments = []): void {
		if ($this->optionBool('debug')) {
			$this->log($message, $arguments);
		}
	}

	/**
	 *
	 * @param string $message
	 * @return mixed
	 */
	public function prompt_yes_no($message, $default = "Y") {
		return to_bool($this->prompt("$message? (yes/no)", $default, [
			"y",
			"yes",
			"n",
			"no",
		]));
	}

	/**
	 *
	 * @param string $message
	 * @return mixed
	 */
	public function prompt($message, $default = null, array $completions = null) {
		if (!$this->prompt) {
			return $this->option("prompt", true);
		}
		return $this->prompt->prompt($message, $default, $completions);
	}

	/**
	 * Getter/setter for the variable map
	 *
	 * @param string|array $name
	 * @param mixed $value
	 * @return self|mixed|array
	 */
	public function variable_map($name = null, $value = null) {
		if (is_array($name)) {
			$this->variable_map = $name + $this->variable_map;
			return $this;
		}
		if (is_string($name)) {
			if ($value === null) {
				return avalue($this->variable_map, $name);
			}
			$this->variable_map[$name] = $value;
			return $this;
		}
		return $this->variable_map;
	}

	/**
	 * Process a command file configuration line
	 *
	 * @param string $line
	 * @return boolean
	 */
	public function process($line) {
		$line = preg_replace("/\s+/", " ", $line);
		[$command, $raw_arguments] = pair($line, " ", $line, null);
		$command = PHP::clean_function($command);
		$raw_arguments = preg_replace("/\s+/", " ", trim($raw_arguments));
		$arguments = $this->map(explode(" ", $raw_arguments));
		$method = "command_$command";
		$__ = compact("command", "raw_arguments", "arguments");
		if (method_exists($this, $method)) {
			$this->verbose_log("Running command {command} {raw_arguments} => {arguments}", $__);
			$result = call_user_func_array([
				$this,
				$method,
			], $arguments);
		} else {
			if ($this->has_hook($method)) {
				$result = $this->call_hook_arguments($method, [
					$arguments,
					$command,
				], null);
			} else {
				$this->error("Unknown command {command} ({raw_arguments})", $__);
				return false;
			}
		}
		if (is_bool($result) && $result === false) {
			$this->error("Command failed ... aborting.");
			return false;
		} else {
			$this->verbose_log("Command {command} was successful (changed={changed})", $__ + [
				"changed" => $this->changed ? "true" : "false",
			]);
		}

		return true;
	}

	/**
	 * Remove a directory on the system with a specified owner and mode
	 *
	 * @return boolean|null Returns true if changes made successfully, false if failed, or null if no changes required
	 */
	private function current_uid_gid() {
		return [
			intval(implode("\n", $this->application->process->execute("id -u"))),
			intval(implode("\n", $this->application->process->execute("id -g"))),
		];
	}

	/**
	 * Delete a directory on the system completely
	 *
	 * @param string $target Directory to remove
	 * @return boolean|null Returns true if changes made successfully, false if failed, or null if no changes required
	 */
	public function command_rmdir($target) {
		$locale = $this->application->locale;
		$target = $this->application->paths->expand($target);
		$__ = [
			"target" => $target,
		];
		if (is_file($target)) {
			$this->error("{target} is a file, not a directory (rmdir)", $__);
			return false;
		}
		if (!is_dir($target)) {
			return null;
		}
		if (!$this->prompt_yes_no($locale->__("Remove directory {target}?", $__))) {
			return null;
		}
		if (!Directory::delete($target)) {
			$this->error("Unable to remove directory {target}", $__);
			return false;
		}
		return true;
	}

	/**
	 * Delete a file on the system completely
	 *
	 * @param string $target File to remove
	 * @return boolean|null Returns true if changes made successfully, false if failed, or null if no changes required
	 */
	public function command_rm($target) {
		$locale = $this->application->locale;
		$target = $this->application->paths->expand($target);
		$__ = [
			"target" => $target,
		];
		if (!is_file($target)) {
			$this->error("{target} is not a a file (rm)", $__);
			return false;
		}
		if (!$this->prompt_yes_no($locale->__("Remove file {target}?", $__))) {
			return null;
		}
		if (!File::unlink($target)) {
			$this->error("Unable to remove file {target}", $__);
			return false;
		}
		return true;
	}

	/**
	 * Create a directory on the system with a specified owner and mode
	 *
	 * @param string $target Directory to create
	 * @param string $owner Owner of the directory (enforced)
	 * @param string|number $want_mode Decimal value or octal string
	 * @return boolean|null Returns true if changes made successfully, false if failed, or null if no changes required
	 */
	public function command_mkdir($target, $flags) {
		$changed = null;
		$locale = $this->application->locale;
		$target = $this->application->paths->expand($target);
		$flags = func_get_args();
		array_shift($flags);
		$flags = $this->parse_file_flags($flags);
		$__['target'] = $target;
		if (!is_dir($target)) {
			if (!$this->prompt_yes_no($locale->__("Create directory {target}?", $__))) {
				return false;
			}
			if (!mkdir($target, null, true)) {
				$this->error("Unable to create directory {target}", $__);
				return false;
			}
			$changed = true;
		}
		$result = $this->handle_owner_mode($target, avalue($flags, 'owner'), avalue($flags, 'mode'));
		if (is_bool($result)) {
			return $result;
		}
		return $changed;
	}

	/**
	 * Execute a shell command - Danger: security implications.
	 * Sanitizes input for the shell.
	 *
	 * @param string $command
	 * @throws Exception_Command
	 * @return array
	 */
	public function exec($command) {
		$args = func_get_args();
		array_shift($args);
		if (count($args) === 1 && is_array($args[0])) {
			$args = $args[0];
		}
		return $this->application->process->execute_arguments($command, $args);
	}

	/**
	 * Syntax:
	 *
	 * changed command-to-run command-parameters
	 *
	 * Run command if changes occurred in previous lines.
	 *
	 * @return NULL|boolean
	 */
	public function command_changed() {
		$command = [];
		$args = func_get_args();
		foreach ($args as $arg) {
			$command[] = escapeshellarg($arg);
		}
		$root_command = $command[0];
		$command = implode(" ", $command);

		$__ = [
			"command" => $command,
		];
		if (!$this->changed) {
			$this->verbose_log("Skipping command {command} as no changes detected", $__);
			return null;
		}
		if (count($args) === 0) {
			$this->verbose_log("Reset changed flag to false");
			$this->changed = false;
			return null;
		}
		if (!$this->prompt_yes_no($this->application->locale->__("Run command \"{command}\" ?", $__))) {
			return false;
		}

		try {
			$this->exec($command);
			$this->verbose_log("Successfully ran {command}", $__);
			return true;
		} catch (\Exception $e) {
			$this->error("Command {command} failed with exit code {code}", $__ + $e->variables());
			return false;
		}
	}

	/**
	 * Require the named services to be installed
	 *
	 * @return boolean|NULL
	 */
	public function command_require_services() {
		if ($this->command_require_binaries("service") === false) {
			return false;
		}
		$args = func_get_args();
		foreach ($args as $service) {
			try {
				$result = $this->exec("service {service} status", [
					"service" => $service,
				]);
			} catch (Exception_Command $e) {
				$this->error("Service {service} failed with output:\n{output}", [
					"service" => $service,
					"output" => implode("\n", $e->output),
				]);
				return false;
			}
		}
		return null;
	}

	/**
	 * Requires the named binaries to exist in the PATH
	 *
	 * @return boolean|NULL
	 */
	public function command_require_binaries() {
		$args = func_get_args();
		foreach ($args as $binary) {
			$which = $this->application->paths->which($binary);
			if (!$which) {
				$this->error("Binary {binary} not available in path {path}", [
					"binary" => $binary,
					"path" => $this->application->paths->command(),
				]);
				return false;
			}
		}
		return null;
	}

	/**
	 * Symlink should link to file
	 *
	 * @param string $symlink
	 * @param string $file
	 * @return boolean|null Returns true if changes made successfully, false if failed, or null if no changes required
	 */
	public function command_symlink($symlink, $file) {
		$symlink = $this->application->paths->expand($symlink);
		$file = $this->application->paths->expand($file);
		$locale = $this->application->locale;
		$__ = compact("symlink", "file");
		if (!is_dir($file) && !is_file($file)) {
			$this->error("Symlink {symlink} => {file}: File does not exist", $__);
			return false;
		}
		$this->verbose_log("Symlink {symlink} => {file}", $__);
		if (!is_link($symlink)) {
			if (file_exists($symlink)) {
				$bytes = filesize($symlink);
				if (!$this->prompt_yes_no($locale->__("Symlink to create \"{symlink}\" exists ({bytes} bytes), delete?", $__))) {
					return false;
				}
				File::unlink($symlink);
			} elseif (is_dir($symlink)) {
				if (!$this->prompt_yes_no($locale->__("Symlink to create \"{symlink}\" is already a directory, delete?", $__))) {
					return false;
				}
				Directory::delete($symlink);
			}
			if (!$this->prompt_yes_no($locale->__("Create symbolic link \n\t{symlink} => {file}\n?", $__))) {
				return false;
			}
		} else {
			if (($oldlink = readlink($symlink)) === $file) {
				return null;
			}
			if (!$this->prompt_yes_no($locale->__("Symlink {symlink} points to {old_file}, update to point to correct {file}?", compact("old_file") + $__))) {
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
	 * Map a string using the current variable map
	 *
	 * @param string|array $string
	 * @return string|array
	 */
	public function map($string) {
		return map($string, $this->variable_map, true);
	}

	/**
	 * Maps ${foo} for file replacements
	 *
	 * @param string $string
	 * @return string
	 */
	public function file_map($string) {
		return map($string, $this->variable_map, true, '${', '}');
	}

	/**
	 * Copy a file from source to destination and inherit parent directory owner and group
	 *
	 * @param string $source Filename of source file
	 * @param string $destination Filename of destination file
	 * @return boolean
	 */
	public static function copy_file_inherit($source, $destination) {
		if (!copy($source, $destination)) {
			return false;
		}

		try {
			File::copy_uid_gid(dirname($destination), $destination);
		} catch (\Exception $e) {
		}
		return true;
	}

	/**
	 * Pass a list of variables which MUST be defined to continue
	 *
	 * @param $variable Pass one or more variables to test that they are defined
	 * @return boolean
	 */
	public function command_defined() {
		$args = func_get_args();
		$not_defined = [];
		foreach ($args as $arg) {
			if (!array_key_exists(strtolower($arg), $this->variable_map)) {
				$not_defined[] = $arg;
			}
		}
		if (count($not_defined) > 0) {
			$this->error("Configure {self} requires the following defined, which are not: {not_defined}\nAll variables: {all_vars}", [
				"not_defined" => $not_defined,
				"all_vars" => array_keys($this->variable_map),
			] + $this->variable_map);
			return false;
		} else {
			$this->verbose_log("defined {args} - success", [
				"args" => implode(" ", $args),
			]);
			return true;
		}
	}

	public function parse_file_flags(array $flags) {
		$flags = to_list(strtr(implode(" ", $flags), [
			"," => " ",
			";" => " ",
		]), [], " ");
		$result = [];
		foreach ($flags as $flag) {
			$flag = trim($flag);
			if (empty($flag)) {
				continue;
			}
			if (str_contains($flag, ":")) {
				$result['owner'] = $flag;
			} elseif (str_starts_with($flag, '0')   || is_numeric($flag)) {
				$result['mode'] = $flag;
			} else {
				if (!array_key_exists($flag, self::$file_flags)) {
					$this->application->logger->warning("Unknown flag {flag} found in {flags}", [
						"flag" => $flag,
						"flags" => $flags,
					]);
				}
				$result[$flag] = true;
			}
		}
		return $result;
	}

	/**
	 *
	 * @param string $source
	 * @param string $destination
	 * @param ... flags space or comma separated - can be:
	 *
	 * owner:,:group,owner:group,0550,map
	 *
	 * Where : designates an owner/group pair, leading 0 is an octal mode, and "map" is the flag to map the source file's contents using ${VARIABLES} before copying to destination.
	 *
	 *
	 *
	 *
	 */
	public function command_file($source, $destination) {
		$source = $this->application->paths->expand($source);
		$destination = $this->application->paths->expand($destination);
		$locale = $this->application->locale;

		$args = func_get_args();
		array_shift($args);
		array_shift($args);
		$flags = $this->parse_file_flags($args);
		if (count($flags) > 0) {
			$this->verbose_log("{method} flags are: {flags}", [
				"method" => __METHOD__,
				"flags" => $flags,
			]);
		}
		$want_owner = avalue($flags, "owner", null);
		$want_mode = avalue($flags, "mode", null);
		$map = avalue($flags, "map");
		$trim = avalue($flags, "trim");
		$__ = compact("source", "destination", "want_owner", "want_mode", "map", "trim");
		$old_source = null;
		if ($map) {
			$contents = file_get_contents($source);
			$new_contents = $this->file_map($contents);
			if ($trim) {
				$new_contents = trim($new_contents);
			}
			if ($new_contents === $contents) {
				$this->application->logger->warning("File {source} unaffected by `map` flag (map:{map} trim:{trim})", $__);
			} else {
				$old_source = $source;
				$source = File::temporary($this->application->paths->temporary());
				file_put_contents($source, $new_contents);
			}
		}
		if (is_link($destination)) {
			if (!$this->prompt_yes_no($locale->__("Target {destination} is a link, replace with {source} as a file?", $__))) {
				return false;
			}
			File::unlink($destination);
			if (!self::copy_file_inherit($source, $destination)) {
				return false;
			}
			return $this->handle_owner_mode($destination, $want_owner, $want_mode);
		}
		switch ($this->files_differ_helper($source, $destination, $old_source)) {
			case "=":
				return $this->handle_owner_mode($destination, $want_owner, $want_mode);
			case "source":
				$result = self::copy_file_inherit($source, $destination);
				if ($old_source) {
					File::unlink($source);
				}
				if (!$result) {
					return $result;
				}
				return $this->handle_owner_mode($destination, $want_owner, $want_mode);
			case "destination":
				$result = self::copy_file_inherit($destination, $source);
				if ($old_source) {
					File::unlink($source);
				}
				if (!$result) {
					return $result;
				}
				return $this->handle_owner_mode($destination, $want_owner, $want_mode);
		}
		$__ = compact("source", "destination");
		if (!file_exists($source)) {
			$this->verbose_log(is_dir(dirname($source)) ? "Source {source} does not exist" : "Source {source} does not exist, nor does its parent directory", $__);
		}
		if ($old_source) {
			File::unlink($source);
		}
		if (!file_exists($destination)) {
			$this->verbose_log(is_dir(dirname($destination)) ? "Destination {destination} does not exist" : "Destination {destination} does not exist, nor does its parent directory", $__);
		}

		return null;
	}

	/**
	 *
	 * @param string $source
	 * @param string $destination
	 * @return boolean|null Returns true if changes made successfully, false if failed, or null if no changes made
	 */
	public function command_file_catenate($source, $destination) {
		$source = $this->application->paths->expand($source);
		$destination = $this->application->paths->expand($destination);
		$locale = $this->application->locale;

		$flags = func_get_args();
		array_shift($flags);
		array_shift($flags);
		$flags = $this->parse_file_flags($flags);

		$sources = File::find_all($this->host_paths, $source);
		$__ = [
			"source" => $source,
			"host_paths" => $this->host_paths,
		];
		if (count($sources) === 0) {
			$this->verbose_log("No file {source} found in {host_paths}", $__);
			$completions = ArrayTools::suffix($this->host_paths, "/" . StringTools::unprefix($source, "/"));
			$__ = [
				"source" => $source,
				"completions" => implode(" ", $this->completions),
			];
			$conf = trim($this->prompt($locale->__("Create {source}? ({completions})", $__), $completions));
			if (in_array($conf, $this->completions)) {
				$__['conf'] = $conf;
				$this->changed = true;
				$this->log("Writing {conf} with empty file for {source}", $__);
				self::file_put_contents_inherit($conf, "");

				try {
					File::copy_uid_gid(dirname($conf), $conf);
				} catch (\Exception $e) {
				}
			}
			return true;
		} else {
			$this->verbose_log("Found files {sources}", [
				"sources" => implode(" ", $sources),
			]);
		}
		$content = "";
		$map = avalue($flags, "map", false);
		$trim = avalue($flags, "trim", false);
		foreach ($sources as $file) {
			$file_content = File::contents($file);
			if ($map) {
				$file_content = $this->file_map($file_content);
			}
			if ($trim) {
				$file_content = trim($file_content) . "\n";
			}
			$content .= $file_content;
		}
		if (trim(File::contents($destination)) === trim($content)) {
			return null;
		}
		$temp_file = File::temporary($this->application->paths->temporary(), "temp");
		file_put_contents($temp_file, $content);
		$result = $this->files_differ_helper($temp_file, $destination, $source);
		File::unlink($temp_file);
		switch ($result) {
			case "=":
				return null;
			case "source":
				return self::file_put_contents_inherit($destination, $content);
			case "destination":
				$default_source = path(last($this->host_paths), $source);
				$this->verbose_log("Copy {source} to {default_source}", compact("source", "default_source"));
				return self::copy_file_inherit($destination, $default_source);
		}
		return false;
	}

	/**
	 * List of files which MUST be included in another file, useful for editing system configruation files.
	 *
	 * Always appended to file with a newline if does not exist.
	 *
	 * @param string $sources One or more source files to incorporate
	 * @param string $destination The target file to include them in
	 *
	 * @return boolean|null Returns true if changes made successfully, false if failed, or null if no changes made
	 */
	public function command_file_edit() {
		$args = func_get_args();
		foreach ($args as $index => $arg) {
			$args[$index] = $this->application->paths->expand($args[$index]);
		}
		$destination = array_pop($args);
		if (!file_exists($destination)) {
			$this->verbose_log("{destination} does not exist, skipping rule", [
				"destination" => $destination,
			]);
			return null;
		}
		$content = file_get_contents($destination);

		$changed = false;
		foreach ($args as $arg) {
			if (!is_file($arg)) {
				$this->application->logger->warning("{arg} passed to {method} for destination {destination}, but {arg} not found", [
					"arg" => $arg,
					"method" => __METHOD__,
					"destination" => $destination,
				]);

				continue;
			}
			$source_content = file_get_contents($arg);
			if (!strpos($content, $source_content)) {
				$this->verbose_log("Source fragment {arg} ({len} bytes) \"{content}\" NOT found in {destination} ({dlen} bytes), inserting", [
					"arg" => $arg,
					"content" => PHP::dump($source_content),
					"len" => strlen($source_content),
					"dlen" => strlen($content),
					"destination" => $destination,
				]);
				$changed = true;
				$content .= "\n" . rtrim($source_content) . "\n";
			} else {
				$this->verbose_log("Source fragment {arg} found in {destination}, no action", [
					"arg" => $arg,
					"destination" => $destination,
				]);
			}
		}
		if ($changed) {
			$temp_file = File::temporary($this->application->paths->temporary(), "temp");
			file_put_contents($temp_file, $content);
			$result = $this->file_update_helper($temp_file, $destination, "computed");
			File::unlink($temp_file);
			switch ($result) {
				case "source":
					return self::file_put_contents_inherit($destination, $content);
				case "destination":
					return null;
			}
		}
		return null;
	}

	/**
	 * Find a string in the command results.
	 *
	 * @param array $result
	 * @param string $string
	 * @param boolean $case_sensitive
	 * @return boolean
	 */
	public function _find_result_string(array $result, $string, $case_sensitive = false) {
		return $case_sensitive ? ArrayTools::find($result, $string) !== false : ArrayTools::ifind($result, $string) !== false;
	}

	/**
	 * Run composer install in a directory
	 *
	 * @param string $path
	 */
	public function command_composer($path) {
		$composer_bin = $this->application->paths->which("composer");
		if (!$composer_bin) {
			$composer_bin = $this->application->paths->which("composer.phar");
			if (!$composer_bin) {
				$this->error("Composer binary not found in PATH={path}, stopping", [
					"path" => $this->application->paths->command(),
				]);
				return false;
			}
		}
		$__ = [
			"path" => $path,
		];
		if (!is_dir($path)) {
			$this->error("Composer must be passed a valid directory: {path}", $__);
			return false;
		}
		$__['composer_json'] = $composer_json = path($path, "composer.json");
		if (!is_readable($composer_json)) {
			$this->error("Composer {path} must contain a composer.json file", $__);
			return false;
		}

		try {
			JSON::decode(file_get_contents($composer_json));
		} catch (\Exception $e) {
			$this->error("Composer {composer_json} is not a valid JSON file", $__);
			return false;
		}
		$args = [];
		$args[] = "install";
		$args[] = "--no-dev";
		$args[] = "--no-ansi";
		$args[] = "--no-interaction";
		$args[] = "--working-dir={path}";
		$args[] = "--optimize-autoloader";

		$command = $composer_bin . " " . implode(" ", $args);
		$result = $this->exec($command . " --dry-run" . self::STDERR_REDIRECT, $__);
		if ($this->_find_result_string($result, "nothing to install")) {
			$this->verbose_log("Composer is up to date in {path}", $__);
			return null;
		}
		if (!$this->prompt_yes_no(__("Run composer install in {path}", $__))) {
			return false;
		}

		try {
			$this->exec($command . self::STDERR_REDIRECT, $__);
		} catch (\Exception $e) {
			$this->error($e);
			return false;
		}
		return true;
	}

	/**
	 * Arguments for any yarn command. Appends argument list with base arguments.
	 *
	 * @param array $args
	 * @return array
	 */
	private function _yarn_generic_args($args = []) {
		$args[] = "--non-interactive"; // No console here, pal.
		$args[] = "--json"; // Output JSON structures, one per line
		$args[] = "--check-files"; //
		$args[] = "--no-progress"; // No progress output
		$args[] = "--silent"; // STFU
		//		$args[] = "--flat"; // Only one version of each package should be installed
		$args[] = "--prod"; // Production packages
		$args[] = "--frozen-lockfile"; // Do not alter lock file
		$args[] = "--cwd {path}"; // Use this as CWD

		return $args;
	}

	/**
	 * Arguments for yarn command to check if yarn install needs to be run
	 *
	 * @return array
	 */
	private function _yarn_check_args() {
		$args = [];
		$args[] = "check"; // Our command
		$args = $this->_yarn_generic_args($args);
		$args[] = "--integrity"; // Check the integrity of the packages
		$args[] = "--verify-tree"; // Check the integrity of the tree as well
		return $args;
	}

	/**
	 * Arguments for yarn command to run yarn install
	 *
	 * @return array
	 */
	private function _yarn_install_args() {
		$args = [];
		$args[] = "install"; // Our command
		$args = $this->_yarn_generic_args($args);
		return $args;
	}

	/**
	 * Yarn outputs JSON structures, one per line. Scan it for errors and return the error strings as an array
	 * @param array $result Result lines
	 * @return array
	 */
	private function _yarn_collect_errors(array $result) {
		$errors = [];
		foreach ($result as $json) {
			$line = JSON::decode($json);
			if ($line['type'] === "error") {
				$errors[] = $line['data'];
			}
		}
		return $errors;
	}

	/**
	 * Run yarn build in a directory
	 *
	 * @param string $path
	 */
	public function command_yarn($path) {
		$yarn_bin = $this->application->paths->which("yarn");
		if (!$yarn_bin) {
			$this->error("Yarn binary not found in PATH={path}, stopping", [
				"path" => $this->application->paths->command(),
			]);
			return false;
		}
		$__ = [
			"path" => $path,
		];
		if (!is_dir($path)) {
			$this->error("Yarn must be passed a valid directory: {path}", $__);
			return false;
		}
		$__['package_json'] = $package_json = path($path, "package.json");
		if (!is_readable($package_json)) {
			$this->error("Yarn {path} must contain a package.json file", $__);
			return false;
		}

		try {
			JSON::decode(file_get_contents($package_json));
		} catch (\Exception $e) {
			$this->error("Yarn {package_json} is not a valid JSON file", $__);
			return false;
		}

		$failed = false;

		try {
			$this->verbose_log("Changed directory to {path}", $__);
			$command = $yarn_bin . " " . implode(" ", $this->_yarn_check_args()) . self::STDERR_REDIRECT;
			$result = $this->exec($command, $__);
		} catch (Exception_Command $e) {
			$result = $e->output;
			$failed = true;
		} catch (\Exception $e) {
			$this->error($e);
			return false;
		}
		$this->verbose_log($result);
		$errors = $this->_yarn_collect_errors($result);
		if (count($errors) === 0 && !$failed) {
			$this->verbose_log("Yarn is up to date in {path}", $__);
			return null;
		}

		try {
			if (!$this->prompt_yes_no(__("Run yarn install in {path}", $__))) {
				return false;
			}
			$command = $yarn_bin . " " . implode(" ", $this->_yarn_install_args()) . self::STDERR_REDIRECT;
			$result = $this->exec($command, $__);
			$this->verbose_log($result);
			$errors = $this->_yarn_collect_errors($result);
			if (count($errors) === 0) {
				$this->verbose_log("Yarn failed to install in {path}:\n\t{errors}", $__ + [
					"errors" => implode("\n\t", $errors),
				]);
				return null;
			}
			$this->verbose_log("Yarn install successful in {path}", $__);
			return true;
		} catch (\Exception $e) {
			$this->error($e);
			return false;
		}
	}

	/**
	 * chdir with logging wrappers
	 *
	 * @param string $path
	 * @param boolean $restoring Flag to change messages when restoring cwd
	 * @return string|null
	 */
	private function _chdir($path, $restoring = false) {
		$cwd = getcwd();
		if (!chdir($path)) {
			$this->error($restoring ? "Can not change directory back to {path}" : "Can not change directory to {path}", [
				"path" => $path,
			]);
			return null;
		}
		$this->verbose_log($restoring ? "Changed directory back to {path}" : "Changed directory back to {path}", [
			"path" => $path,
		]);
		return $cwd;
	}

	/**
	 * Copy content to a destination file and inherit parent directory owner and group
	 *
	 * @param string $source Filename of source file
	 * @param string $content File contents (string)
	 * @return boolean
	 */
	public static function file_put_contents_inherit($destination, $content) {
		if (!file_put_contents($destination, $content)) {
			return false;
		}

		try {
			File::copy_uid_gid(dirname($destination), $destination);
		} catch (\Exception $e) {
			throw $e;
		}
		return true;
	}

	/**
	 *
	 * @param unknown $target
	 * @param unknown $want_owner
	 * @param string|number $want_mode Decimal string or octal string
	 * @return boolean|null Returns true if changes made successfully, false if failed, or null if no changes required
	 */
	private function handle_owner_mode($target, $want_owner = null, $want_mode = null) {
		$locale = $this->application->locale;

		$original_want_mode = $want_mode;
		if (is_string($want_mode)) {
			if (preg_match('/^0[0-9]+$/', $want_mode)) {
				$want_mode = intval(octdec($want_mode));
			} else {
				$want_mode = intval($want_mode);
			}
		} elseif (is_int($want_mode)) {
			$want_mode = intval($want_mode);
		}
		if ($want_mode === 0) {
			$this->error("{method} invalid \$want_mode with value {original}", [
				"method" => __METHOD__,
				"original" => $original_want_mode,
			]);
			return false;
		} elseif ($want_mode !== null && !is_int($want_mode)) {
			$this->error("{method} invalid \$want_mode {type} with value {original} -> {final}", [
				"method" => __METHOD__,
				"original" => $original_want_mode,
				"final" => $want_mode,
				"type" => type($original_want_mode),
			]);
			return false;
		}
		$changed = null;
		$new_user = null;
		$new_group = null;
		$stats = File::stat($target, "");
		$__['target'] = $target;
		$__['want_owner'] = $want_owner;
		$__['want_mode_octal'] = "0" . decoct($want_mode);
		$__['want_mode'] = $want_mode;

		$__['old_mode'] = $old_mode = $stats['perms']['decimal'];
		$__['old_mode_octal'] = $stats['perms']['octal0'];
		$__['old_user'] = $stats['owner']['owner'];
		$__['old_group'] = $stats['owner']['group'];
		if (!empty($want_owner)) {
			$this->verbose_log("Want owner of {target} to be {want_owner} ...", $__);
			[$want_user, $want_group] = pair($want_owner, ":", $want_owner, null);
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
				if (!$this->prompt_yes_no($locale->__("Change owner of {target} to {want_owner} (old {old_user}:{old_group}) ?", $__))) {
					return false;
				}
				if ($new_user) {
					if (!chown($target, $new_user)) {
						$this->error("Unable to chown {target} to {new_user} (old user {old_user})", $__);
						return false;
					}
					$this->verbose_log("Changed owner of {target} to {new_user} (old user {old_user})", $__);
					$changed = true;
				}
				if ($new_group) {
					if (!chgrp($target, $new_group)) {
						$this->error("Unable to chgrp {target} to {new_group} (old group {old_group})", $__);
						return false;
					}
					$this->verbose_log("Changed group of {target} to {new_group} (old group {old_group})", $__);
					$changed = true;
				}
			}
		}
		if (!empty($want_mode)) {
			$this->verbose_log("Want mode of {target} to be {want_mode_octal} ...", $__);
			if ($old_mode !== $want_mode) {
				if (!$this->prompt_yes_no($locale->__("Change permissions of {target} to {want_mode_octal} (old mode {old_mode_octal})?", $__))) {
					return false;
				}
				if (!chmod($target, $want_mode)) {
					$this->error("Unable to chmod {target} to {want_mode_octal} (decimal: {decimal_want_mode})", $__);
					return false;
				}
				$changed = true;
			}
		}
		return $changed;
	}

	/**
	 * Show differences between two files
	 *
	 * @param string $source
	 * @param string $destination
	 * @param string|NULL $source_name
	 * @param string|NULL $destination_name
	 * @return string[2] List of source/destination names
	 */
	private function _show_differences($source, $destination, $source_name = null, $destination_name = null) {
		if ($source_name === null) {
			$source_name = $source;
		}
		if ($destination_name === null) {
			$destination_name = $destination;
		}
		$this->log("Files differ: < $source_name > $destination_name");

		try {
			$this->application->process->execute_arguments("diff {0} {1}", [
				$source,
				$destination,
			], true);
		} catch (\Exception $e) {
		}
		return [
			$source_name,
			$destination_name,
		];
	}

	public function files_are_identical($source, $destination) {
		try {
			$this->application->process->execute("diff -w {0} {1}", $source, $destination);
			return true;
		} catch (Exception_Command $e) {
			// Not the same
		}
		return false;
	}

	/**
	 * Prompt to update a file (overwrite only)
	 *
	 * @param string $source
	 * @param string $destination
	 * @param string|NULL $source_name Optional "real" source name
	 * @param string|NULL $destination_name Optional "real" destination name
	 * @return null|boolean null means files are the same, boolean means yes/no update it
	 */
	public function file_update_helper($source, $destination, $destination_name = null) {
		$locale = $this->application->locale;

		if ($this->files_are_identical($source, $destination)) {
			return null;
		}
		[$source_name, $destination_name] = $this->_show_differences($source, $destination, null, $destination_name);
		return $this->prompt_yes_no($locale->__("Update destination {destination}?", [
			"destination" => $destination_name,
		]));
	}

	/**
	 * Prompt to update a file (bi-directional copy)
	 *
	 * @param string $source
	 * @param string $destination
	 * @param string|NULL $source_name Optional "real" source name
	 * @param string|NULL $destination_name Optional "real" destination name
	 */
	private function files_differ_helper($source, $destination, $source_name = null, $destination_name = null) {
		if ($this->files_are_identical($source, $destination)) {
			return "=";
		}
		[$source_name, $destination_name] = $this->_show_differences($source, $destination, $source_name, $destination_name);
		$completions = [
			"source",
			"<",
			"destination",
			"skip",
		];
		switch (trim($this->prompt("Which is right?\n< source, > destination, or skip? (<,source,>,destination,skip) Default: source", "source", $completions))) {
			case ">":
			case "destination":
				$this->log("Copying {destination_name} to {source_name}", compact("source_name", "destination_name"));
				$this->changed = true;
				return "destination";
			case "skip":
				$this->log("skipping ...");
				$this->incomplete++;
				return null;
			default:
			case "<":
			case "source":
				$this->log("Copying {source_name} to {destination_name}", compact("source_name", "destination_name"));
				$this->changed = true;
				return "source";
		}
	}
}
