<?php declare(strict_types=1);

/**
 * @package zesk
 * @subpackage Daemon
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk\Daemon;

use zesk\Application;
use zesk\ArrayTools;
use zesk\Command_Base;
use zesk\Exception_Configuration;
use zesk\Exception_File_Permission;
use zesk\Exception_Semantics;
use zesk\Exception_Syntax;
use zesk\Exception_System;
use zesk\FilesMonitor;
use zesk\Interface_Process;
use zesk\PHP;
use zesk\ProcessTools;
use zesk\StringTools;
use zesk\Text;
use zesk\Timer;
use zesk\Timestamp;

/**
 * Run daemons associated with an application.
 * Runs a continual process for each daemon hook encountered, and restarts daemons
 * as needed when they fail.
 *
 * @author kent
 * @category Management
 */
class Command extends Command_Base implements Interface_Process {
	protected array $shortcuts = ['daemon'];

	/**
	 *
	 * @var string
	 */
	protected string $help = 'Run daemons associated with an application. Runs a continual process for each daemon hook encountered, and restarts daemons as needed when they fail. Individual control over selected processes by using --up --down, and --bounce. You can run multiple processes per method by adding a configuration option `ClassName::daemon::process_count`. Note that this process will only fork a maximum of 100 child processes unless you increase the limit using configuration option `Command_Daemon::maximum_processes`.';

	/**
	 *
	 * @var FilesMonitor
	 */
	private FilesMonitor $watch_monitor;

	public const OPTION_NO_FORK = 'no-fork';

	/**
	 *
	 * @var array
	 */
	protected array $option_types = [
		'debug-log' => 'boolean',
		self::OPTION_NO_FORK => 'boolean',
		'nohup' => 'boolean',
		'stop' => 'boolean',
		'kill' => 'boolean',
		'list' => 'boolean',
		'stat' => 'boolean',
		'cron' => 'boolean',
		'down' => 'string',
		'up' => 'string',
		'bounce' => 'string',
		'terminate-after' => 'integer',
		'alive-interval' => 'integer',
		'terminate-wait' => 'integer',
		'watch' => 'file[]',
	];

	/**
	 *
	 * @var array
	 */
	protected array $option_help = [
		'debug-log' => 'Output log information',
		self::OPTION_NO_FORK => 'Don\'t fork - useful for debugging single processes',
		'nohup' => 'Fork and run as a daemon process, ignore HUP signals',
		'stop' => 'Stop all daemon processes',
		'kill' => 'Kill all daemon processes',
		'list' => 'List all daemon methods which would be invoked',
		'stat' => 'Output statistics for a running server',
		'down' => 'Bring down a single method within a server',
		'cron' => 'Use this option to run the daemon via cron - it won\'t output an error message if the daemon is already running.',
		'up' => 'Bring up a single method within a server',
		'bounce' => 'Restart a single method within a server',
		'terminate-after' => 'Quit after the number of seconds specified; often assists with processes which work for a while then seize up.',
		'terminate-wait' => 'Wait number of seconds after termination before re-launching.',
		'alive-interval' => 'Number of seconds after which to output a message, to prove server is alive.',
		'watch' => 'One or more files which, when their attributes change, should trigger the daemon to exit.',
	];

	/**
	 * FP to fifo: Read on server
	 *
	 * @var resource
	 */
	private mixed $fifo_r = null;

	/**
	 * FP to fifo: Write on client
	 *
	 * @var resource
	 */
	private mixed $fifo_w = null;

	/**
	 * Path to fifo
	 *
	 * @var string
	 */
	private string $fifo_path;

	/**
	 * Module daemon
	 *
	 * @var Module
	 */
	protected Module $module;

	/**
	 * Am I the parent?
	 *
	 * @var boolean
	 */
	private bool $parent = true;

	/**
	 * Name of this daemon.
	 * May have a ^ suffix for processes which should have multiple instances.
	 *
	 * @var string
	 */
	private string $name = '';

	/**
	 * Method of this daemon
	 *
	 * @var string
	 */
	private string $method = '';

	/**
	 *
	 * @var boolean
	 */
	private bool $quitting = false;

	/**
	 * Set to true in subclasses to skip Application configuration until ->go
	 *
	 * @var boolean
	 */
	public bool $has_configuration = true;

	/**
	 * Signal names to strings
	 *
	 * @var array
	 */
	public static array $signals = [
		SIGINT => 'SIGINT',
		SIGCHLD => 'SIGCHLD',
		SIGALRM => 'SIGALRM',
		SIGTERM => 'SIGTERM',
		SIGHUP => 'SIGHUP',
	];

	/**
	 * @param Application $set
	 * @return Interface_Process
	 */
	public function setApplication(Application $set): Interface_Process {
		$this->application = $set;
		return $this;
	}

	/**
	 * Command MAIN
	 *
	 * @see Command::run()
	 */
	public function run(): int {
		$daemon = $this->application->modules->object('Daemon');
		assert($daemon instanceof Module);
		$this->module = $daemon;

		PHP::requires(PHP::FEATURE_PROCESS_CONTROLL, true);

		$this->configure('daemon', true);

		if (!$this->application->isConfigured()) {
			throw new Exception_Configuration('Application is not configured', 'Application is not configured');
		}

		if ($this->optionBool('debug-log')) {
			echo Text::format_pairs(ArrayTools::filterKeyPrefixes($this->application->configuration->toArray(), 'log'));
		}

		$this->fifo_path = path($this->module->runPath, 'daemon-controller');

		if ($this->optionBool('kill')) {
			return $this->command_stop(SIGKILL);
		}
		if ($this->optionBool('stop')) {
			return $this->command_stop();
		}
		if ($this->optionBool('list')) {
			return $this->commandList();
		}
		if ($this->optionBool('stat')) {
			return $this->command_stat();
		}
		if ($this->hasOption('up')) {
			return $this->commandState($this->option('up'), 'up');
		}
		if ($this->hasOption('down')) {
			return $this->commandState($this->option('down'), 'down');
		}
		if ($this->hasOption('bounce')) {
			return $this->commandState($this->option('bounce'), 'down', 'up');
		}

		return $this->command_run();
	}

	/**
	 * @return Application
	 */
	public function application(): Application {
		return $this->application;
	}

	protected function install_signals(): void {
		if (function_exists('pcntl_signal')) {
			$callback = $this->signal_handler(...);
			pcntl_signal(SIGCHLD, $callback);
			pcntl_signal(SIGTERM, $callback);
			pcntl_signal(SIGINT, $callback);
			pcntl_signal(SIGALRM, $callback);

			register_shutdown_function($this->shutdown(...));
		}
	}

	/**
	 * @return array
	 * @throws Exception_File_Permission
	 * @throws Exception_Syntax
	 */
	private function loadProcessDatabase(): array {
		return $this->module->loadProcessDatabase();
	}

	private function saveProcessDatabase(array $database): void {
		$this->module->saveProcessDatabase($database);
	}

	/**
	 * Send $signal to parent process, terminating all processes eventually.
	 *
	 * @return number
	 */
	final public function command_stop($signal = SIGTERM): int {
		$database = $this->loadProcessDatabase();
		if (count($database) === 0) {
			$this->application->logger->error('Not running.');
			return 1;
		}
		$signal_name = self::$signals[$signal] ?? $signal;
		$me = $database['me'] ?? null;
		if (!$me) {
			$changed = false;
			$this->application->logger->error('Parent process has been terminated - killing children');
			var_dump($database);
			foreach ($database as $name => $settings) {
				$pid = $settings['pid'];
				$status = $settings['status'];
				if ($status === 'up') {
					if (!posix_kill($pid, $signal)) {
						$this->application->logger->notice('Dead process {name} ({pid})', compact('name', 'pid'));
						unset($database[$name]);
						$changed = true;
					} else {
						$this->application->logger->notice('Sent {signal_name} to {name} ({pid})', compact('name', 'pid'));
					}
				} else {
					unset($database[$name]);
					$changed = true;
				}
			}
			if ($changed) {
				$this->saveProcessDatabase($database);
			}
			return 0;
		}

		$pid = $me['pid'];
		$this->application->logger->notice('Sent {signal} to process {pid}', [
			'pid' => $pid,
			'signal' => $signal_name,
		]);
		posix_kill($pid, $signal);
		return 0;
	}

	/**
	 * Fetch list of daemons
	 *
	 * @return Closure[]
	 */
	private function daemons(): array {
		$daemon_hooks = [
			"zesk\Application::daemon",
			"zesk\Module::daemon",
		];
		$daemon_hooks = $this->callHookArguments('daemon_hooks', [
			$daemon_hooks,
		], $daemon_hooks);
		$this->debugLog('Daemon hooks are {daemon_hooks}', [
			'daemon_hooks' => $daemon_hooks,
		]);
		$daemons = $this->application->hooks->findAll($daemon_hooks);
		$daemons = $this->daemons_expand($daemons);
		return $daemons;
	}

	/**
	 *
	 * @param array $daemons
	 * @throws Exception_System
	 * @return string[]
	 */
	private function daemons_expand(array $daemons): array {
		$total_process_count = 0;
		$configuration = $this->application->configuration;
		$new_daemons = [];
		$max = $this->option('maximum_processes', 100);
		foreach ($daemons as $daemon) {
			$process_count = toInteger($configuration->getPath("$daemon::process_count"), 1);
			if ($process_count <= 1) {
				$total_process_count = $total_process_count + 1;
				$new_daemons[] = $daemon;
			} else {
				$total_process_count = $total_process_count + $process_count;
				for ($i = 0; $i < $process_count; $i++) {
					$new_daemons[] = $daemon . '^' . $i;
				}
			}
		}
		// Prevent idiot errors
		if ($total_process_count > $max) {
			throw new Exception_System('Total daemon processes are {total_process_count}, greater than {max} processes. Update {class}::process_count configuration or fix code so fewer processes are created.', [
				'total_process_count' => $total_process_count,
				'class' => __CLASS__,
				'max' => $max,
			]);
		}
		return $new_daemons;
	}

	/**
	 * List all daemons to be run
	 *
	 * @return number
	 */
	final public function commandList(): int {
		$daemons = $this->daemons();
		echo Command . phpimplode(PHP_EOL, $daemons) . PHP_EOL;
		return 0;
	}

	/**
	 *
	 * @param string $name
	 * @param string $want
	 * @param string $newState
	 * @return int
	 */
	final public function commandState(string $name, string $want, string $newState = ''): int {
		$database = $this->loadProcessDatabase();
		if ($name === 'all') {
			foreach ($database as $name => $settings) {
				$this->_commandState($database, $name, $settings, $want, $newState);
			}
			return 0;
		}
		$settings = $database[$name] ?? null;
		if (!is_array($settings)) {
			$found = false;
			foreach ($database as $procname => $settings) {
				if (stripos($procname, $name) !== false) {
					$this->application->logger->notice('Matched process name {procname}', compact('procname'));
					$this->_commandState($database, $procname, $settings, $want, $newState);
					$found = true;
				}
			}
			if ($found) {
				return 0;
			}
			$settings = null;
		}
		if (!is_array($settings)) {
			$this->application->logger->error('Unknown process {name}', [
				'name' => $name,
			]);
			return 2;
		}
		return $this->_commandState($database, $name, $settings, $want);
	}

	/**
	 * Change state of a daemon process
	 *
	 * @param array $database
	 * @param string $name Daemon name
	 * @param array $settings Current settings
	 * @param string $want Desired state
	 * @param string $newState
	 * @return number
	 */
	private function _commandState(array $database, string $name, array $settings, string $want, string $newState = ''):
	int {
		$pid = $settings['pid'];
		$status = $settings['status'];
		if ($status === $want) {
			return 0;
		}
		$database[$name]['status'] = $newState === '' ? $want : $newState;
		$database[$name]['time'] = microtime(true);
		$this->saveProcessDatabase($database);
		if ($want === 'down') {
			$this->send([
				$name => null,
			]);
			posix_kill($pid, SIGTERM);
			$timer = new Timer();
			$killed = false;
			do {
				usleep(20000);
				if (!$killed && $timer->elapsed() > 3) {
					posix_kill($pid, SIGKILL);
					$killed = true;
				}
				if ($timer->elapsed() > 10) {
					$this->application->logger->error('Unable to kill process {name} ({pid})', [
						'name' => $name,
						'pid' => $pid,
					]);
					return 1;
				}
			} while (!posix_kill($pid, 0));
			$this->send();
		} elseif ($want === 'up') {
			$this->send();
		}
		return 0;
	}

	/**
	 *
	 * @return number
	 */
	final public function command_stat() {
		$database = $this->loadProcessDatabase();
		$changed = false;
		foreach ($database as $name => $settings) {
			$pid = $settings['pid'];
			$status = $settings['status'];
			if ($status === 'up' && !posix_kill($pid, 0)) {
				unset($database[$name]);
				$changed = true;
			}
		}
		if ($changed) {
			$this->saveProcessDatabase($database);
		}
		if (count($database) === 0) {
			echo "Not running.\n";
		} else {
			$pairs = [];
			$now = microtime(true);
			foreach ($database as $name => $settings) {
				$pid = $settings['pid'];
				$time = $settings['time'];
				$status = $settings['status'];
				$delta = round($now - $time);
				$is_running = $pid && posix_kill($pid, 0);
				$want = '';
				$status_text = $status;
				$suffix = " (pid $pid)";
				if ($status === 'up' && !$is_running) {
					$status_text = 'down';
					$want = ', want up';
				} elseif ($status === 'down') {
					if ($is_running) {
						$status_text = 'up';
						$want = ', want down';
					} else {
						$suffix = '';
					}
				}
				$pairs[$name] = "$status_text$suffix, " . $this->application->locale->plural_number(
					'second',
					intval($delta)
				) . $want;
			}
			echo Text::format_pairs($pairs);
		}
		return 0;
	}

	public static function shutdown(): void {
		$instance = self::instance();
		$instance->terminate();
	}

	/**
	 *
	 * @return self
	 */
	protected static function instance(): self {
		if (!self::$instance) {
			throw new Exception_Semantics('No instance');
		}
		return self::$instance;
	}

	/**
	 *
	 * @param self $set
	 * @return self
	 */
	protected static function setInstance(self $set): void {
		self::$instance = $set;
	}

	/**
	 * The unix signal handler for multi-process systems
	 *
	 * @param int $signo
	 *        	The signal number to handle
	 */
	public function signal_handler(int $signo): void {
		$this->application->logger->debug('Signal {signame} {signo} received', [
			'signo' => $signo,
			'signame' => self::$signals[$signo] ?? 'Unknown',
		]);
		if (function_exists('pcntl_signal')) {
			switch ($signo) {
				case SIGINT:
					$this->terminate('Interrupt signal received');

					break;
				case SIGCHLD:
					$this->send(); // Wake up server
					break;
				case SIGALRM:
					break;
				case SIGTERM:
					$this->terminate('Termination signal received');

					break;
				case SIGHUP:
					if (!$this->optionBool('nohup')) {
						$this->terminate('Hangup signal received');
					}

					break;
				default:
					break;
			}
		}
	}

	/**
	 * Send a message to parent process using FIFO
	 *
	 * @param string $message
	 */
	private function send(mixed $message = ''): void {
		if ($this->optionBool(self::OPTION_NO_FORK)) {
			return;
		}
		$this->_fifo_write();
		if ($message === '') {
			$n = 0;
			$data = '';
		} else {
			$data = serialize($message);
			$n = strlen($data);
		}
		fwrite($this->fifo_w, "$n\n$data");
		fflush($this->fifo_w);
	}

	/**
	 * Read a message as server
	 *
	 * @param int $timeout in seconds
	 * @return mixed
	 */
	private function read(int $timeout): mixed {
		if ($this->quitting) {
			return null;
		}
		if (!$this->fifo_r) {
			return null;
		}
		$readers = [
			$this->fifo_r,
		];
		$writers = [];
		$except = [];
		$sec = $timeout;
		$usec = ($timeout - $sec) * 1000000;

		declare(ticks = 1) {
			// KMD: Safely ignore E_WARNING about interrupted system call here TODO 2023 PHP 8.1
			set_error_handler(fn () => true, E_WARNING);
			$result = @stream_select($readers, $writers, $except, $sec, $usec);
			restore_error_handler();
			if ($result) {
				$n = intval(fgets($this->fifo_r));
				if ($n === 0) {
					return [];
				}
				return unserialize(fread($this->fifo_r, $n));
			}
			return null;
		}
	}

	/**
	 * @return int
	 */
	private function daemonize(): int {
		if ($this->optionBool('nofork')) {
			return 0;
		}
		$this->error('pcntl_fork {file}:{line}', [
			'file' => __FILE__,
			'line' => __LINE__,
		]);
		$pid = pcntl_fork();
		if ($pid === 0) {
			$this->application->hooks->call('pcntl_fork-child');
			/* We are the child */
			$sid = posix_setsid();
			if ($sid < 0) {
				$this->error('Unable to posix_setsid - can not run with --nohup');
				exit(1);
			}
			$this->setOption('sid', $sid);
			umask(0);
			chdir('/');
			fclose(STDIN);
			fclose(STDOUT);
			fclose(STDERR);
		} else {
			$this->application->hooks->call('pcntl_fork-parent');
		}
		return $pid;
	}

	/**
	 * Main loop for daemon
	 *
	 * @throws Exception_File_Permission
	 * @return number
	 */
	final public function command_run(): int {
		$database = $this->loadProcessDatabase();
		assert(is_array($database));
		$my_db_pid = apath($database, 'me.pid');
		if ($my_db_pid !== null && posix_kill($my_db_pid, 0)) {
			if (!$this->optionBool('cron')) {
				$this->error('Daemon already running.');
			}
			return 1;
		}

		if ($this->optionBool('nohup')) {
			$pid = $this->daemonize();
			if ($pid !== 0) {
				$this->log("Launched daemon PID $pid\n");
				return 0;
			}
			/* We are the child */
		}
		$database['me'] = [
			'pid' => $this->application->process->id(),
			'status' => 'up',
			'time' => microtime(true),
		];
		$this->saveProcessDatabase($database);
		$this->application->logger->notice('Daemon run successfully.');
		self::instance($this);

		PHP::setFeature('time_limit', $this->optionInt('time_limit', 0));
		$daemons = $this->daemons();
		$this->application->logger->debug('Daemons to run: ' . implode(', ', $daemons));

		if (file_exists($this->fifo_path)) {
			if (!unlink($this->fifo_path)) {
				throw new Exception_File_Permission($this->fifo_path, 'unlink(\'{filename}\')');
			}
		}
		if (!posix_mkfifo($this->fifo_path, 0o600)) {
			throw new Exception_File_Permission($this->fifo_path, 'mkfifo {filename}');
		}

		$this->_fifo_read();
		$timeout = $this->optionInt('child read timeout', 1);
		$this->install_signals();
		$terminate_after = $this->optionInt('terminate-after', 0);
		$timer = new Timer();
		$alive_notices = 0;
		if (count($daemons) === 0) {
			$this->warning('No daemons found to run');
		}
		$this->load_watch();

		declare(ticks = 1) {
			do {
				$this->check_watch();
				$this->run_children();
				$this->read_fifo($timeout);
				$elapsed = $timer->elapsed();
				if ($terminate_after > 0 && $elapsed > $terminate_after) {
					$this->terminate("Stopping after $terminate_after seconds");
				}
				if ($elapsed > $alive_notices) {
					$alive_notices = $elapsed + $this->option('alive-interval', 600);
					$this->application->logger->notice('Daemon is running at {date}', [
						'date' => Timestamp::now()->format(),
					]);
				}
			} while (!$this->done());
		}

		return 0;
	}

	/**
	 *
	 */
	private function load_watch(): void {
		$files = $this->optionIterable('watch');
		if (count($files) > 0) {
			$this->watch_monitor = new FilesMonitor($files);
		}
	}

	private function check_watch(): void {
		if ($this->watch_monitor) {
			if ($this->watch_monitor->changed()) {
				$this->quitting = true;
			}
		}
	}

	private function run_child($name) {
		$pid = $this->application->process->id();
		$this->application->logger->debug('FORKING for process {name} me={pid}', [
			'name' => $name,
			'pid' => $pid,
		]);
		$nofork = $this->optionBool('nofork');
		if ($nofork) {
			$this->application->logger->warning('Not forking for child process because of --nofork');
			$child = 0;
		} else {
			$child = pcntl_fork();
		}
		if ($child < 0) {
			$this->application->logger->error('Unable to fork to run {name}', [
				'name' => $name,
			]);
			return null;
		} elseif ($child === 0) {
			$this->application->hooks->call('pcntl_fork-child');
			$this->application->logger->notice('Running {name} as process id {pid}', [
				'name' => $name,
				'pid' => $this->application->process->id(),
			]);
			$this->name = $name;
			$this->method = StringTools::left($name, '^', $name);
			$this->child();
			// ->child() exits process always - exit here for documentation
			exit(0);
		}
		$this->application->hooks->call('pcntl_fork-parent');

		$this->application->logger->debug('PARENT FORKED for process {name} me={pid} child={child}', [
			'name' => $name,
			'pid' => $pid,
			'child' => $child,
		]);
		while (true) {
			$status = 0;
			$result = pcntl_waitpid($child, $status, WNOHANG);
			if ($result !== 0) {
				usleep(100);
			} else {
				break;
			}
		}
		return [
			'pid' => $child,
			'status' => 'up',
			'time' => microtime(true),
		];
	}

	/**
	 * Read messages from FIFO and update the database, if needed
	 *
	 * @param int $timeout
	 */
	private function read_fifo(int $timeout): void {
		if ($this->debug) {
			$this->application->logger->debug('Server waiting for data in FIFO (timeout is {timeout} seconds)', [
				'timeout' => $timeout,
			]);
		}
		$result = $this->read($timeout);
		if ($this->debug) {
			$this->application->logger->debug('Server read: {data}', [
				'data' => var_export($result, true),
			]);
		}
		if (!is_array($result)) {
			return;
		}
		$database = $this->loadProcessDatabase();
		foreach ($result as $name => $pid) {
			if (is_numeric($pid)) {
				if (!array_key_exists($name, $database)) {
					$this->application->logger->error('Child sent name which isn\'t in our database? {name}', [
						'name' => $name,
					]);
				} elseif ($database[$name]['pid'] !== $pid) {
					$this->application->logger->error('Child sent PID which doesn\'t match our database (Child sent {childpid}, we have {pid}?', $database[$name] + [
						'childpid' => $pid,
					]);
				}
			} elseif ($pid === null || $pid === 'down') {
				// Child dying or died, be sure to waitpid for it to allow safe exit
				$childpid = apath($database, [
					$name,
					'pid',
				]);
				$status = 0;
				pcntl_waitpid($childpid, $status);
				if ($pid === 'down') {
					$database[$name]['status'] = 'down';
					$this->application->logger->error('Service {name} requested to go down', [
						'name' => $name,
					]);
				}
			} else {
				$this->application->logger->debug('Unknown message from child: {child_pid}', [
					'data' => serialize($pid),
				]);
			}
		}
		$this->saveProcessDatabase($database);
	}

	/**
	 * Run all of our children
	 */
	private function run_children(): void {
		$daemons = $this->daemons();
		$database = $this->loadProcessDatabase();
		foreach ($daemons as $name) {
			$settings = $database[$name] ?? null;
			if ($settings === null) {
				$settings = $this->run_child($name);
				if (is_array($settings)) {
					$database[$name] = $settings;
					$this->saveProcessDatabase($database);
				}
			} else {
				$pid = $settings['pid'];
				$status = $settings['status'];
				$pcntl_status = 0;
				if (($wait = pcntl_waitpid($pid, $pcntl_status, WNOHANG)) > 0) {
					$this->application->logger->debug('Child process {name} {pid} exited with {pcntl_status} (result = {wait})', compact('name', 'wait', 'pcntl_status', 'pid'));
					unset($database[$name]);
					$this->saveProcessDatabase($database);

					continue;
				}
				if (posix_kill($pid, 0)) {
					if ($status === 'down') {
						$this->application->logger->debug('Sending TERM to {name} ({pid}) - want down', [
							'pid' => $pid,
							'name' => $name,
						]);
						posix_kill($pid, SIGTERM);

						continue;
					}
					if ($this->debug) {
						$this->application->logger->debug('Checking {name} {pid}: Running', [
							'name' => $name,
							'pid' => $pid,
						]);
					}
				} elseif ($status === 'down') {
					continue;
				} elseif ($status === 'up') {
					$settings = $this->run_child($name);
					if (is_array($settings)) {
						$database[$name] = $settings;
						$this->saveProcessDatabase($database);
					}
				} else {
					$errno = posix_get_last_error();
					$this->application->logger->debug('{name} REMOVED {pid} NOT RUNNING {errno}: {strerror}', [
						'name' => $name,
						'pid' => $database[$name],
						'errno' => $errno,
						'strerror' => posix_strerror($errno),
					]);
					unset($database[$name]);
					$this->saveProcessDatabase($database);
				}
			}
		}
		while (($wait = pcntl_waitpid(-1, $status, WNOHANG)) > 0) {
			$this->application->logger->debug('Child process {wait} exited with {status}', compact('wait', 'status'));
		}
	}

	/**
	 * Open write FIFO
	 *
	 * @throws Exception_File_Permission
	 */
	private function _fifo_write(): void {
		if (is_resource($this->fifo_w)) {
			return;
		}
		$this->fifo_w = fopen($this->fifo_path, 'wb');
		if (!$this->fifo_w) {
			$this->application->logger->error('Can not open fifo {fifo} for writing', [
				'fifo' => $this->fifo_path,
			]);

			throw new Exception_File_Permission($this->fifo_path, 'fopen(\'{filename}\', \'w\')');
		}
	}

	/**
	 * Open read FIFO (used by parent process only)
	 *
	 * @throws Exception_File_Permission
	 */
	private function _fifo_read(): void {
		$this->fifo_r = fopen($this->fifo_path, 'r+b');
		if (!$this->fifo_r) {
			$this->application->logger->error('Can not open fifo {fifo} for reading', [
				'fifo' => $this->fifo_path,
			]);

			throw new Exception_File_Permission($this->fifo_path, 'fopen(\'{filename}\', \'r\')');
		}
		stream_set_blocking($this->fifo_r, false);
		stream_set_read_buffer($this->fifo_r, 0);
	}

	/**
	 * Close read FIFO
	 */
	private function _fifo_read_close(): void {
		if ($this->fifo_r) {
			fclose($this->fifo_r);
			$this->fifo_r = null;
		}
	}

	/**
	 * Close write FIFO
	 */
	private function _fifo_write_close(): void {
		if ($this->fifo_w) {
			fclose($this->fifo_w);
			$this->fifo_w = null;
		}
	}

	/**
	 * Close all FIFOs
	 */
	private function _fifos_close(): void {
		$this->_fifo_read_close();
		$this->_fifo_write_close();
	}

	/**
	 * Run child process, then exit
	 */
	private function child(): void {
		$this->parent = false;
		if ($this->optionBool('nofork')) {
			$this->_fifos_close();
		} else {
			$this->_fifo_read_close();
			$this->_fifo_write();
		}
		$this->install_signals();
		$pid = $this->application->process->id();
		$this->send([
			$this->name => $pid,
		]);
		$this->application->logger->debug('Running {name} as part of {pid}', [
			'name' => $this->name,
			'pid' => $pid,
		]);
		$result = call_user_func($this->method, $this);
		if ($result === 'down') {
			$this->send([
				$this->name => 'down',
			]);
		} else {
			$this->send([
				$this->name => null,
			]);
		}
		$this->_fifos_close();
		exit();
	}

	/**
	 * Last time quitting values were called and checked
	 *
	 * @var double
	 */
	private $last_tick = null;

	/**
	 * Getter for done state
	 *
	 * @return boolean
	 */
	public function done(): bool {
		pcntl_signal_dispatch();
		if ($this->quitting) {
			return true;
		}
		$now = microtime(true);
		if ($this->last_tick === null || $now - $this->last_tick > 1.0) {
			$this->last_tick = $now;
			if (ProcessTools::includesChanged($this->application)) {
				$this->warning('Code changed - exiting.');
				$this->quitting = true;
			}
			$nobj = gc_collect_cycles();
			if ($nobj > 0) {
				$this->log('Collected {nobj} object cycles', [
					'nobj' => $nobj,
				]);
			}
			$this->read_fifo(0);
		}
		return $this->quitting;
	}

	/**
	 * Kill/interrupt this process.
	 * Harsher than ->done(true);
	 *
	 * @param string $interrupt
	 */
	public function kill(): void {
		$this->quitting = true;
		$myProcessID = $this->application->process->id();
		if ($this->parent) {
			$database = $this->loadProcessDatabase();
			foreach ($database as $pid) {
				if ($myProcessID !== $pid) {
					posix_kill($pid, SIGKILL);
				} else {
					posix_kill($pid, SIGTERM);
				}
			}
		}
	}

	/**
	 *
	 * @param array $database
	 */
	private function shutdown_children(array $database): void {
		foreach ($database as $name => $settings) {
			$pid = $status = null;
			extract($settings, EXTR_IF_EXISTS);
			if ($status === 'up' && $pid) {
				$this->application->logger->debug('Sending SIGINT to {pid}', [
					'pid' => $pid,
				]);
				posix_kill($pid, SIGINT);
			}
		}
		while (count($database) > 1) {
			usleep(intval(0.1 * 1000000));
			foreach ($database as $name => $settings) {
				$pid = $settings['pid'];
				$status = $settings['status'];
				if ($pid === $this->application->process->id()) {
					continue;
				}
				if ($status === 'down') {
					unset($database[$name]);

					continue;
				}
				$status = 0;
				$result = pcntl_waitpid($pid, $status, WNOHANG);
				if ($result === -1) {
					$this->application->logger->error('pcntl_waitpid({pid}, {status}, WNOHANG) returned -1, child died? {name}', [
						'name' => $name,
						'pid' => $pid,
						'status' => $status,
					]);
					unset($database[$name]);
				} elseif ($result === 0) {
					$this->application->logger->debug('pcntl_waitpid({pid}, {status}, WNOHANG) returned 0, no child available. {name}.', [
						'name' => $name,
						'pid' => $pid,
						'status' => $status,
					]);
					unset($database[$name]);
				} else {
					if (pcntl_wifexited($status)) {
						unset($database[$name]);
						$this->application->logger->debug('pcntl_wifexited({status}) {pid} success {name}', [
							'name' => $name,
							'pid' => $pid,
							'status' => $status,
						]);
					}
				}
			}
		}
	}

	/**
	 * Terminate this process.
	 * Nice way to do it.
	 */
	public function terminate($message = null): void {
		if ($this->quitting) {
			return;
		}
		if ($message) {
			$this->application->logger->notice($message);
		}
		$this->quitting = true;
		if ($this->parent) {
			$database = $this->loadProcessDatabase();
			if (count($database) > 0) {
				$this->shutdown_children($database);
			} else {
				$this->application->logger->error('Database is empty on termination? ...');
			}
			usleep(intval(0.1 * 1000000));
			$this->application->logger->debug('Deleting FIFO and database ...');
			unlink($this->fifo_path);
			$this->module->unlink_database();
		} else {
			$this->send();
			if ($this->hasOption('terminate-wait')) {
				sleep($this->optionInt('terminate-wait', 1));
			}
			$this->application->logger->notice('Daemon child terminated ...');
			exit();
		}
	}

	/**
	 * Take a nap.
	 * I love naps.
	 */
	public function sleep($seconds = 1.0): void {
		pcntl_signal_dispatch();
		if ($this->quitting) {
			$this->terminate();
			return;
		}
		$remain = $seconds;
		$timer = new Timer();

		declare(ticks = 1) {
			do {
				$remain = $seconds - $timer->elapsed();
				$min_seconds = max(min($remain, 0.1), 0);
				if ($min_seconds === 0) {
					break;
				}
				$usleep = intval($min_seconds * 1000000);
				usleep($usleep);
			} while (!$this->done() && $timer->elapsed() < $seconds);
		}
	}

	/**
	 * Logging tool for processes
	 *
	 * @param string $message
	 * @param array $args
	 * @param string $level
	 */
	public function warning(string $message, array $args = []): void {
		$this->application->logger->warning($message, $args);
	}
}
