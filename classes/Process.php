<?php

/**
 * @package zesk
 * @subpackage kernel
 * @copyright &copy; 2017 Market Acumen, Inc.
 */
namespace zesk;

/**
 * Current and other process status, process creation
 *
 * @author kent
 *
 */
class Process {
	/**
	 *
	 * @var boolean
	 */
	public $debug = false;

	/**
	 *
	 * @var Application
	 */
	private $application = null;

	/**
	 *
	 */
	public function __sleep() {
		return array(
			"debug",
		);
	}

	/**
	 *
	 */
	public function __wakeup() {
		$this->application = __wakeup_application();
	}

	/**
	 * Create object
	 */
	public function __construct(Application $application) {
		$this->application = $application;
		$application->hooks->add(Hooks::HOOK_CONFIGURED, array(
			$this,
			"configured",
		));
	}

	/**
	 * Current process id
	 *
	 * @return integer
	 */
	public function id() {
		return intval(getmypid());
	}

	/**
	 * Return current process owner user name
	 *
	 * @return string
	 */
	public function user() {
		return posix_getlogin();
	}

	/**
	 *
	 * @param Application $application
	 */
	public function configured(Application $application) {
		$key = array(
			__CLASS__,
			"debug_execute",
		);
		$application->configuration->deprecated("zesk::debug_execute", $key);
		$this->debug = $application->configuration->path_get($key);
	}

	/**
	 *
	 * @param integer $pid
	 * @throws \Exception_Unimplemented
	 * @return boolean
	 */
	public function alive($pid) {
		if (!function_exists("posix_kill")) {
			throw new Exception_Unimplemented("Need --with-pcntl");
		}
		return posix_kill($pid, 0) ? true : false;
	}

	/**
	 * Execute a shell command.
	 *
	 * Usage is:
	 * <pre>
	 * zesk::execute("ls -d {0}", $dir);
	 * </pre>
	 * Arguments are indexed and passed through. If you'd prefer named arguments, use
	 * execute_arguments.
	 * You can pass in a pipe character as the first character of the command to enable the passthru
	 * flag, so
	 *
	 * <code>
	 * $process->execute("|ls -lad {0}", $dir);
	 * </code>
	 *
	 * is equivalent to:
	 *
	 * <code>
	 * $process->execute_arguments("ls -lad {0}", array($dir), true);
	 * </code>
	 *
	 * @param string $command
	 * @return array Lines output by the command (returned by exec)
	 * @see exec
	 * @see self::execute_arguments
	 * @throws Exception_Command
	 */
	public function execute($command) {
		$args = func_get_args();
		array_shift($args);
		if ($command[0] === "|") {
			$command = substr($command, 1);
			$passthru = true;
		} else {
			$passthru = false;
		}
		return $this->execute_arguments($command, $args, $passthru);
	}

	/**
	 * Execute a shell command with arguments supplied as an array
	 *
	 * Usage is:
	 * <pre>
	 * zesk::execute("ls -d {dir}", array("dir" => $dir));
	 * </pre>
	 *
	 * Non-zero output status of the command throws an exception, always. If you expect failures,
	 * catch the exception:
	 *
	 * <code>
	 * try {
	 * zesk::execute("mount {0}", $volume);
	 * } catch (Exception_Command $e) {
	 * echo "Volume mount failed: $volume\n" . $e->getMessage(). "\n";
	 * }
	 * </code>
	 *
	 * @param string $command
	 *        	Command to run
	 * @param array $args
	 *        	Arguments to escape and pass into the command
	 * @param boolean $passthru
	 *        	Whether to use passthru vs exec
	 * @throws Exception_Command
	 * @return array Lines output by the command (returned by exec)
	 * @see exec
	 */
	public function execute_arguments($command, array $args = array(), $passthru = false) {
		foreach ($args as $i => $arg) {
			$args[$i] = escapeshellarg($arg);
		}
		$args["*"] = implode(" ", array_values($args));
		$raw_command = map($command, $args);
		$result = 0;
		$output = array();
		if ($this->debug) {
			$this->application->logger->debug("Running command: {raw_command}", compact("raw_command"));
		}
		if ($passthru) {
			passthru($raw_command, $result);
			$output = null;
		} else {
			exec($raw_command, $output, $result);
		}
		if (intval($result) !== 0) {
			throw new Exception_Command($raw_command, $result, is_array($output) ? $output : array());
		}
		return $output;
	}
}
