<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage kernel
 * @copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk;

use zesk\Application\Hooks;
use zesk\Exception\CommandFailed;
use zesk\Exception\FileNotFound;
use zesk\Exception\FilePermission;
use zesk\Exception\SemanticsException;

/**
 * Current and other process status, process creation
 */
class Process {
	/**
	 * Debugging enabled for execute
	 */
	public const OPTION_DEBUG_EXECUTE = 'debugExecute';

	/**
	 *
	 * @var boolean
	 */
	public bool $debug = false;

	/**
	 *
	 * @var Application
	 */
	private Application $application;

	/**
	 *
	 */
	public function __sleep() {
		return [
			'debug',
		];
	}

	/**
	 * @return void
	 * @throws SemanticsException
	 */
	public function __wakeup(): void {
		$this->application = Kernel::wakeupApplication();
	}

	/**
	 * Create object
	 * @throws SemanticsException
	 */
	public function __construct(Application $application) {
		$this->application = $application;
		$application->hooks->add(Hooks::HOOK_CONFIGURED, [
			$this,
			'configured',
		]);
	}

	/**
	 * Current process id
	 *
	 * @return integer
	 */
	public function id(): int {
		return intval(getmypid());
	}

	/**
	 * Return current process owner username
	 *
	 * @return string
	 */
	public function user(): string {
		$login = posix_getlogin();
		if ($login !== false) {
			return $login;
		}
		$uid = posix_getuid();

		try {
			return System::users()[$uid] ?? "uid-$uid";
		} catch (FileNotFound|FilePermission) {
			return "uid-$uid";
		}
	}

	/**
	 *
	 * @param Application $application
	 */
	public function configured(Application $application): void {
		$this->debug = $application->configuration->path(__CLASS__)->getBool(self::OPTION_DEBUG_EXECUTE);
	}

	/**
	 *
	 * @param int $pid
	 * @return boolean
	 */
	public static function alive(int $pid): bool {
		return posix_kill($pid, 0);
	}

	/**
	 *
	 * @param int $pid
	 * @return boolean
	 */
	public static function term(int $pid): bool {
		return posix_kill($pid, SIGTERM);
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
	 * @throws CommandFailed
	 * @see self::executeArguments
	 * @see exec
	 */
	public function execute(string $command): array {
		$args = func_get_args();
		array_shift($args);
		if ($command[0] === '|') {
			$command = substr($command, 1);
			$passthru = true;
		} else {
			$passthru = false;
		}
		return $this->executeArguments($command, $args, $passthru);
	}

	public const EXEC = 'exec';

	public const PASS = 'pass';

	public const SHELL = 'shell';

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
	 * } catch (CommandFailed $e) {
	 * echo "Volume mount failed: $volume\n" . $e->getMessage(). "\n";
	 * }
	 * </code>
	 *
	 * @param string $command
	 *        	Command to run
	 * @param array $args
	 *        	Arguments to escape and pass into the command
	 * @param bool $passThru Whether to use passthru vs exec
	 * @throws CommandFailed
	 * @return array Lines output by the command (returned by exec)
	 * @see exec
	 */
	public function executeArguments(string $command, array $args = [], bool $passThru = false): array {
		$raw_command = $this->generateCommand($command, $args);
		$output = [];
		if ($passThru) {
			passthru($raw_command, $result);
		} else {
			exec($raw_command, $output, $result);
		}
		if ($result !== 0) {
			throw new CommandFailed($raw_command, $result, is_array($output) ? $output : []);
		}
		return $output;
	}

	private function generateCommand($command, array $args): string {
		foreach ($args as $i => $arg) {
			$args[$i] = escapeshellarg(strval($arg));
		}
		$args['*'] = implode(' ', array_values($args));
		$raw_command = ArrayTools::map($command, $args);
		if ($this->debug) {
			$this->application->logger->debug('Running command: {raw_command}', compact('raw_command'));
		}
		return $raw_command;
	}

	/**
	 * @param string $command Command
	 * @param array $args Arguments
	 * @param string $stdout Optional output file
	 * @param string $stderr Optional error file
	 * @return int Process ID of background process
	 * @throws CommandFailed
	 */
	public function executeBackground(string $command, array $args = [], string $stdout = '', string $stderr = ''):
	int {
		$raw_command = $this->generateCommand($command, $args);
		$stdout = escapeshellarg($stdout ?: '/dev/null');
		$stderr = escapeshellarg($stderr ?: '/dev/null');
		$processId = shell_exec("$raw_command > $stdout 2> $stderr & echo $!");
		if (!$processId) {
			throw new CommandFailed("shell_exec($raw_command) failed", 254, []);
		}
		return intval($processId);
	}
}
