<?php
/**
 * @copyright &copy; 2016 Market Acumen, Inc.
 */
namespace zesk;

/**
 * Run daemons associated with an application. Runs a continual process for each daemon hook encountered, and restarts daemons
 * as needed when they fail.
 *
 * @author kent
 * @category Management
 */
class Command_Daemon extends Command_Base implements Interface_Process {

	/**
	 *
	 * @var string
	 */
	protected $help = "Run daemons associated with an application. Runs a continual process for each daemon hook encountered, and restarts daemons as needed when they fail. Individual control over selected processes by using --up --down, and --bounce. You can run multiple processes per method by adding a configuration option `ClassName::daemon::process_count`. Note that this process will only fork a maximum of 100 child processes unless you increase the limit using configuration option `Command_Daemon::maximum_processes`.";

	/**
	 *
	 * @var array
	 */
	protected $option_types = array(
		"debug-log" => "boolean",
		"nofork" => "boolean",
		"nohup" => "boolean",
		"stop" => "boolean",
		"kill" => "boolean",
		"list" => "boolean",
		"stat" => "boolean",
		"cron" => "boolean",
		"down" => "string",
		"up" => "string",
		"bounce" => "string",
		"terminate-after" => "integer",
		"alive-interval" => "integer",
		"terminate-wait" => "integer"
	);

	/**
	 *
	 * @var array
	 */
	protected $option_help = array(
		"debug-log" => "Output log information",
		"nofork" => "Don't fork - useful for debugging single processes",
		"nohup" => "Fork and run as a daemon process, ignore HUP signals",
		"stop" => "Stop all daemon processes",
		"kill" => "Kill all daemon processes",
		"list" => "List all daemon methods which would be invoked",
		"stat" => "Output statistics for a running server",
		"down" => "Bring down a single method within a server",
		"cron" => "Use this option to run the daemon via cron - it won't output an error message if the daemon is already running.",
		"up" => "Bring up a single method within a server",
		"bounce" => "Restart a single method within a server",
		"terminate-after" => "Quit after the number of seconds specified; often assists with processes which work for a while then seize up.",
		"terminate-wait" => "Wait number of seconds after termination before re-launching.",
		"alive-interval" => "Number of seconds after which to output a message, to prove server is alive."
	);

	/**
	 * FP to fifo: Read on server
	 *
	 * @var resource
	 */
	private $fifo_r = null;

	/**
	 * FP to fifo: Write on client
	 *
	 * @var resource
	 */
	private $fifo_w = null;

	/**
	 * Path to fifo
	 *
	 * @var string
	 */
	private $fifo_path = null;

	/**
	 * Module daemon
	 *
	 * @var Module_Daemon
	 */
	protected $module = null;

	/**
	 * Am I the parent?
	 *
	 * @var boolean
	 */
	private $parent = true;

	/**
	 * Name of this daemon. May have a ^ suffix for processes which should have multiple instances.
	 *
	 * @var boolean
	 */
	private $name = true;

	/**
	 * Method of this daemon
	 *
	 * @var boolean
	 */
	private $method = true;

	/**
	 *
	 * @var boolean
	 */
	private $quitting = false;

	/**
	 * Logging for database file read/writes
	 *
	 * @var boolean
	 */
	private $db_debug = false;

	/**
	 * Set to true in subclasses to skip Application configuration until ->go
	 *
	 * @var boolean
	 */
	public $has_configuration = false;

	/**
	 * Signal names to strings
	 *
	 * @var array
	 */
	static $signals = array(
		SIGINT => "SIGINT",
		SIGCHLD => "SIGCHLD",
		SIGALRM => "SIGALRM",
		SIGTERM => "SIGTERM",
		SIGHUP => "SIGHUP"
	);

	/**
	 * Command MAIN
	 *
	 * @see Command::run()
	 */
	function run() {
		$this->module = $this->application->modules->object("daemon");

		PHP::requires('pcntl', true);

		$this->configure("daemon", true);
		if ($this->option_bool('debug-log')) {
			echo Text::format_pairs(arr::filter_prefix($this->application->configuration->to_array(), "log"));
		}

		$this->fifo_path = path($this->module->rundir, "daemon-controller");

		if ($this->option_bool('kill')) {
			return $this->command_stop(SIGKILL);
		}
		if ($this->option_bool('stop')) {
			return $this->command_stop();
		}
		if ($this->option_bool("list")) {
			return $this->command_list();
		}
		if ($this->option_bool("stat")) {
			return $this->command_stat();
		}
		if ($this->has_option("up")) {
			return $this->command_state($this->option("up"), "up");
		}
		if ($this->has_option("down")) {
			return $this->command_state($this->option("down"), "down");
		}
		if ($this->has_option("bounce")) {
			return $this->command_state($this->option("bounce"), "down", "up");
		}

		return $this->command_run();
	}
	public function application(Application $set = null) {
		if ($set) {
			$this->application = $set;
			return $this;
		}
		return $this->application;
	}
	protected function install_signals() {
		if (function_exists("pcntl_signal")) {
			$callback = array(
				$this,
				'signal_handler'
			);
			pcntl_signal(SIGCHLD, $callback);
			pcntl_signal(SIGTERM, $callback);
			pcntl_signal(SIGINT, $callback);
			pcntl_signal(SIGALRM, $callback);

			register_shutdown_function(array(
				__CLASS__,
				"shutdown"
			));
		}
	}

	/**
	 * Get/set daemon process database
	 *
	 * @param array $database
	 * @return array
	 */
	private function _process_database(array $database = null) {
		return $this->module->process_database($database);
	}

	/**
	 * Send $signal to parent process, terminating all processes eventually.
	 *
	 * @return number
	 */
	public final function command_stop($signal = SIGTERM) {
		$database = $this->_process_database();
		if (count($database) === 0) {
			$this->application->logger->error("Not running.");
			return 1;
		}
		$signal_name = avalue(self::$signals, $signal, $signal);
		$me = avalue($database, 'me');
		if (!$me) {
			$changed = false;
			$this->application->logger->error("Parent process has been terminated - killing children");
			var_dump($database);
			foreach ($database as $name => $settings) {
				$pid = $status = $time = null;
				extract($settings, EXTR_IF_EXISTS);
				if ($status === "up") {
					if (!posix_kill($pid, $signal)) {
						$this->application->logger->notice("Dead process {name} ({pid})", compact("name", "pid"));
						unset($database[$name]);
						$changed = true;
					} else {
						$this->application->logger->notice("Sent {signal_name} to {name} ({pid})", compact("name", "pid"));
					}
				} else {
					unset($database[$name]);
					$changed = true;
				}
			}
			if ($changed) {
				$this->_process_database($database);
			}
			return 0;
		}

		$pid = $me['pid'];
		$this->application->logger->notice("Sent {signal} to process {pid}", array(
			"pid" => $pid,
			"signal" => $signal_name
		));
		posix_kill($pid, $signal);
		return 0;
	}

	/**
	 * Fetch list of daemons
	 *
	 * @return Closure[string]
	 */
	private function daemons() {
		$daemon_hooks = array(
			"zesk\Application::daemon",
			"zesk\Module::daemon"
		);
		$daemon_hooks = $this->call_hook_arguments("daemon_hooks", array(
			$daemon_hooks
		), $daemon_hooks);
		$this->debug_log("Daemon hooks are {daemon_hooks}", array(
			"daemon_hooks" => $daemon_hooks
		));
		$daemons = $this->application->hooks->find_all($daemon_hooks);
		$daemons = $this->daemons_expand($daemons);
		return $daemons;
	}

	/**
	 *
	 * @param array $daemons
	 * @throws Exception_System
	 * @return string[]|unknown[]
	 */
	private function daemons_expand(array $daemons) {
		$total_process_count = 0;
		$configuration = $this->application->configuration;
		$new_daemons = array();
		$max = $this->option("maximum_processes", 100);
		foreach ($daemons as $daemon) {
			$process_count = to_integer($configuration->path_get("$daemon::process_count"), 1);
			if ($process_count <= 1) {
				$total_process_count = $total_process_count + 1;
				$new_daemons[] = $daemon;
			} else {
				$total_process_count = $total_process_count + $process_count;
				for ($i = 0; $i < $process_count; $i++) {
					$new_daemons[] = $daemon . "^" . $i;
				}
			}
		}
		// Prevent idiot errors
		if ($total_process_count > $max) {
			throw new Exception_System("Total daemon processes are {total_process_count}, greater than {max} processes. Update {class}::process_count configuration or fix code so fewer processes are created.", array(
				"total_process_count" => $total_process_count,
				"class" => __CLASS__,
				"max" => $max
			));
		}
		return $new_daemons;
	}

	/**
	 * List all daemons to be run
	 *
	 * @return number
	 */
	public final function command_list() {
		$daemons = $this->daemons();
		echo implode(newline(), $daemons) . newline();
		return 0;
	}

	/**
	 *
	 * @param unknown $name
	 * @param unknown $want
	 * @param unknown $newstate
	 * @return number
	 */
	public final function command_state($name, $want, $newstate = null) {
		$database = $this->_process_database();
		if ($name === "all") {
			foreach ($database as $name => $settings) {
				$this->_command_state($database, $name, $settings, $want, $newstate);
			}
			return 0;
		}
		$settings = avalue($database, $name);
		if (!is_array($settings)) {
			$found = false;
			foreach ($database as $procname => $settings) {
				if (stripos($procname, $name) !== false) {
					$this->application->logger->notice("Matched process name {procname}", compact("procname"));
					$this->_command_state($database, $procname, $settings, $want, $newstate);
					$found = true;
				}
			}
			if ($found) {
				return 0;
			}
			$settings = null;
		}
		if (!is_array($settings)) {
			$this->application->logger->error("Unknown process {name}", array(
				"name" => $name
			));
			return 2;
		}
		return $this->_command_state($database, $name, $settings, $want);
	}

	/**
	 * Change state of a daemon process
	 *
	 * @param
	 *        	process name $name
	 * @param array $settings
	 * @param unknown $want
	 * @return number
	 */
	private function _command_state(array $database, $name, array $settings, $want, $newstate = null) {
		$pid = $status = null;
		extract($settings, EXTR_IF_EXISTS);
		if ($status === $want) {
			return 0;
		}
		$database[$name]['status'] = $newstate === null ? $want : $newstate;
		$database[$name]['time'] = microtime(true);
		$this->_process_database($database);
		if ($want === "down") {
			$this->send(array(
				$name => null
			));
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
					$this->application->logger->error("Unable to kill process {name} ({pid})", array(
						"name" => $name,
						"pid" => $pid
					));
					return 1;
				}
			} while (!posix_kill($pid, 0));
			$this->send();
		} else if ($want === "up") {
			$this->send();
		}
		return 0;
	}

	/**
	 *
	 * @return number
	 */
	public final function command_stat() {
		$database = $this->_process_database();
		$changed = false;
		foreach ($database as $name => $settings) {
			$pid = $status = $time = null;
			extract($settings, EXTR_IF_EXISTS);
			if ($status === "up" && !posix_kill($pid, 0)) {
				unset($database[$name]);
				$changed = true;
			}
		}
		if ($changed) {
			$this->_process_database($database);
		}
		if (count($database) === 0) {
			echo "Not running.\n";
		} else {
			$pairs = array();
			$now = microtime(true);
			foreach ($database as $name => $settings) {
				$pid = $status = $time = null;
				extract($settings, EXTR_IF_EXISTS);
				$delta = round($now - $time);
				$is_running = $pid ? posix_kill($pid, 0) : false;
				$want = "";
				$status_text = $status;
				$suffix = " (pid $pid)";
				if ($status === "up" && !$is_running) {
					$status_text = "down";
					$want = ", want up";
				} else if ($status === "down") {
					if ($is_running) {
						$status_text = "up";
						$want = ", want down";
					} else {
						$suffix = "";
					}
				}
				$pairs[$name] = "$status_text$suffix, " . Locale::plural_number("second", $delta) . $want;
			}
			echo Text::format_pairs($pairs);
		}
		return 0;
	}
	public static function shutdown() {
		$instance = self::instance();
		$instance->terminate();
	}

	/**
	 *
	 * @param self $set
	 * @return self
	 */
	protected static function instance($set = null) {
		static $instance = null;
		if (is_object($set)) {
			$instance = $set;
		}
		return $instance;
	}

	/**
	 * The unix signal handler for multi-process systems
	 *
	 * @param integer $signo
	 *        	The signal number to handle
	 */
	public function signal_handler($signo) {
		$this->application->logger->debug("Signal {signame} {signo} received", array(
			'signo' => $signo,
			'signame' => avalue(self::$signals, $signo, 'Unknown')
		));
		if (function_exists("pcntl_signal")) {
			switch ($signo) {
				case SIGINT:
					$this->terminate("Interrupt signal received");
					break;
				case SIGCHLD:
					$this->send(); // Wake up server
					break;
				case SIGALRM:
					break;
				case SIGTERM:
					$this->terminate("Termination signal received");
					break;
				case SIGHUP:
					if (!$this->option_bool("nohup")) {
						$this->terminate("Hangup signal received");
					}
					break;
				default :
					break;
			}
		}
	}
	/**
	 * Send a message to parent process using FIFO
	 *
	 * @param string $message
	 */
	private function send($message = null) {
		if ($this->nofork) {
			return;
		}
		$this->_fifo_write();
		if ($message === null) {
			$n = 0;
			$data = "";
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
	 * @param integer $timeout
	 *        	in seconds
	 * @return NULL multitype: mixed
	 */
	private function read($timeout) {
		if ($this->quitting) {
			return null;
		}
		if (!$this->fifo_r) {
			return null;
		}
		$readers = array(
			$this->fifo_r
		);
		$writers = array();
		$except = array();
		$sec = intval($timeout);
		$usec = ($timeout - $sec) * 1000000;

		declare(ticks = 1) {
			// KMD: Safely ignore E_WARNING about interrupted system call here
			set_error_handler(function () {
				return true;
			}, E_WARNING);
			$result = @stream_select($readers, $writers, $except, $sec, $usec);
			restore_error_handler();
			if ($result) {
				$n = intval(fgets($this->fifo_r));
				if ($n === 0) {
					return array();
				}
				return unserialize(fread($this->fifo_r, $n));
			}
			return null;
		}
	}
	private function daemonize() {
		if ($this->option_bool('nofork')) {
			return 0;
		}
		$this->error("pcntl_fork {file}:{line}", array(
			"file" => __FILE__,
			"line" => __LINE__
		));
		$pid = pcntl_fork();
		if ($pid === 0) {
			$this->application->hooks->call('pcntl_fork-child');
			/* We are the child */
			$this->sid = posix_setsid();
			if ($this->sid < 0) {
				$this->error("Unable to posix_setsid - can not run with --nohup");
				exit(1);
			}
			umask(0);
			chdir("/");
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
	public final function command_run() {
		$database = $this->_process_database();
		assert(is_array($database));
		$my_db_pid = apath($database, 'me.pid');
		if ($my_db_pid !== null && posix_kill($my_db_pid, 0)) {
			if (!$this->option_bool('cron')) {
				$this->error("Daemon already running.");
			}
			return 1;
		}

		if ($this->option_bool('nohup')) {
			$pid = $this->daemonize();
			if ($pid === 0) {
				/* We are the child */
			} else {
				$this->log("Launched daemon PID $pid\n");
				return 0;
			}
		}
		$database['me'] = array(
			'pid' => $this->application->process->id(),
			'status' => 'up',
			'time' => microtime(true)
		);
		$this->_process_database($database);
		$this->application->logger->notice("Daemon run successfully.");
		self::instance($this);

		PHP::feature("time_limit", $this->option_integer("time_limit", 0));
		$daemons = $this->daemons();
		$this->application->logger->debug("Daemons to run: " . implode(", ", $daemons));

		if (file_exists($this->fifo_path)) {
			if (!unlink($this->fifo_path)) {
				throw new Exception_File_Permission($this->fifo_path, "unlink('{filename}')");
			}
		}
		if (!posix_mkfifo($this->fifo_path, 0600)) {
			throw new Exception_File_Permission($this->fifo_path, "mkfifo {filename}");
		}

		$this->_fifo_read();
		$timeout = $this->option_integer("child read timeout", 1);
		$this->install_signals();
		$terminate_after = $this->option_integer('terminate-after', 0);
		$timer = new Timer();
		$alive_notices = 0;
		if (count($daemons) === 0) {
			$this->warning("No daemons found to run");
		}
		declare(ticks = 1) {
			do {
				$this->run_children();
				$this->read_fifo($timeout);
				$elapsed = $timer->elapsed();
				if ($terminate_after > 0 && $elapsed > $terminate_after) {
					$this->terminate("Stopping after $terminate_after seconds");
				}
				if ($elapsed > $alive_notices) {
					$alive_notices = $elapsed + $this->option('alive-interval', 600);
					$this->application->logger->notice("Daemon is running at {date}", array(
						"date" => Timestamp::now()->format()
					));
				}
			} while (!$this->done());
		}

		return 0;
	}
	private function run_child($name) {
		$pid = $this->application->process->id();
		$this->application->logger->debug("FORKING for process {name} me={pid}", array(
			"name" => $name,
			"pid" => $pid
		));
		$nofork = $this->option_bool("nofork");
		if ($nofork) {
			$this->application->logger->warning("Not forking for child process because of --nofork");
			$child = 0;
		} else {
			$child = pcntl_fork();
		}
		if ($child < 0) {
			$this->application->logger->error("Unable to fork to run {name}", array(
				"name" => $name
			));
			return null;
		} else if ($child === 0) {
			$this->application->hooks->call('pcntl_fork-child');
			$this->application->logger->notice("Running {name} as process id {pid}", array(
				"name" => $name,
				"pid" => $this->application->process->id()
			));
			$this->name = $name;
			$this->method = str::left($name, "^", $name);
			$this->child();
			// ->child() exits process always - exit here for documentation
			exit(0);
		}
		$this->application->hooks->call('pcntl_fork-parent');

		$this->application->logger->debug("PARENT FORKED for process {name} me={pid} child={child}", array(
			"name" => $name,
			"pid" => $pid,
			"child" => $child
		));
		while (true) {
			$status = null;
			$result = pcntl_waitpid($child, $status, WNOHANG);
			if ($result !== 0) {
				usleep(100);
			} else {
				break;
			}
		}
		return array(
			'pid' => $child,
			'status' => 'up',
			'time' => microtime(true)
		);
	}

	/**
	 * Read messages from FIFO and update the database, if needed
	 *
	 * @param unknown $timeout
	 */
	private function read_fifo($timeout) {
		if ($this->debug) {
			$this->application->logger->debug("Server waiting for data in FIFO (timeout is {timeout} seconds)", array(
				"timeout" => $timeout
			));
		}
		$result = $this->read($timeout);
		if ($this->debug) {
			$this->application->logger->debug("Server read: {data}", array(
				'data' => var_export($result, true)
			));
		}
		if (!is_array($result)) {
			return;
		}
		$database = $this->_process_database();
		foreach ($result as $name => $pid) {
			if (is_numeric($pid)) {
				if (!array_key_exists($name, $database)) {
					$this->application->logger->error("Child sent name which isn't in our database? {name}", array(
						"name" => $name
					));
				} else if ($database[$name]['pid'] !== $pid) {
					$this->application->logger->error("Child sent PID which doesn't match our database (Child sent {childpid}, we have {pid}?", $database[$name] + array(
						"childpid" => $pid
					));
				}
			} else if ($pid === null || $pid === "down") {
				// Child dying or died, be sure to waitpid for it to allow safe exit
				$childpid = apath($database, array(
					$name,
					'pid'
				));
				$status = null;
				pcntl_waitpid($childpid, $status);
				if ($pid === "down") {
					$database[$name]['status'] = "down";
					$this->application->logger->error("Service {name} requested to go down", array(
						"name" => $name
					));
				}
			} else {
				$this->application->logger->debug("Unknown message from child: {child_pid}", array(
					'data' => serialize($pid)
				));
			}
		}
		$this->_process_database($database);
	}

	/**
	 * Run all of our children
	 */
	private function run_children() {
		$daemons = $this->daemons();
		$database = $this->_process_database();
		foreach ($daemons as $name) {
			$settings = avalue($database, $name);
			if ($settings === null) {
				$settings = $this->run_child($name);
				if (is_array($settings)) {
					$database[$name] = $settings;
					$this->_process_database($database);
				}
			} else {
				$status = $pid = null;
				extract($settings, EXTR_IF_EXISTS);
				$pcntl_status = null;
				if (($wait = pcntl_waitpid($pid, $pcntl_status, WNOHANG)) > 0) {
					$this->application->logger->debug("Child process {name} {pid} exited with {pcntl_status} (result = {wait})", compact("name", "wait", "pcntl_status", "pid"));
					unset($database[$name]);
					$this->_process_database($database);
					continue;
				}
				if (posix_kill($pid, 0)) {
					if ($status === 'down') {
						$this->application->logger->debug("Sending TERM to {name} ({pid}) - want down", array(
							"pid" => $pid,
							"name" => $name
						));
						posix_kill($pid, SIGTERM);
						continue;
					}
					if ($this->debug) {
						$this->application->logger->debug("Checking {name} {pid}: Running", array(
							"name" => $name,
							"pid" => $pid
						));
					}
					continue;
				} else if ($status === "down") {
					continue;
				} else if ($status === "up") {
					$settings = $this->run_child($name);
					if (is_array($settings)) {
						$database[$name] = $settings;
						$this->_process_database($database);
					}
				} else {
					$errno = posix_get_last_error();
					$this->application->logger->debug("{name} REMOVED {pid} NOT RUNNING {errno}: {strerror}", array(
						"name" => $name,
						"pid" => $database[$name],
						"errno" => $errno,
						"strerror" => posix_strerror($errno)
					));
					unset($database[$name]);
					$this->_process_database($database);
				}
			}
		}
		while (($wait = pcntl_waitpid(-1, $status, WNOHANG)) > 0) {
			$this->application->logger->debug("Child process {wait} exited with {status}", compact("wait", "status"));
		}
	}

	/**
	 * Open write FIFO
	 *
	 * @throws Exception_File_Permission
	 */
	private function _fifo_write() {
		if (is_resource($this->fifo_w)) {
			return;
		}
		$this->fifo_w = fopen($this->fifo_path, "w");
		if (!$this->fifo_w) {
			$this->application->logger->error("Can not open fifo {fifo} for writing", array(
				"fifo" => $this->fifo_path
			));
			throw new Exception_File_Permission($this->fifo_path, "fopen('{filename}', 'w')");
		}
	}

	/**
	 * Open read FIFO (used by parent process only)
	 *
	 * @throws Exception_File_Permission
	 */
	private function _fifo_read() {
		$this->fifo_r = fopen($this->fifo_path, "r+");
		if (!$this->fifo_r) {
			$this->application->logger->error("Can not open fifo {fifo} for reading", array(
				"fifo" => $this->fifo_path
			));
			throw new Exception_File_Permission($this->fifo_path, "fopen('{filename}', 'r')");
		}
		stream_set_blocking($this->fifo_r, false);
		stream_set_read_buffer($this->fifo_r, 0);
	}

	/**
	 * Close read FIFO
	 */
	private function _fifo_read_close() {
		if ($this->fifo_r) {
			fclose($this->fifo_r);
			$this->fifo_r = null;
		}
	}
	/**
	 * Close write FIFO
	 */
	private function _fifo_write_close() {
		if ($this->fifo_w) {
			fclose($this->fifo_w);
			$this->fifo_w = null;
		}
	}

	/**
	 * Close all FIFOs
	 */
	private function _fifos_close() {
		$this->_fifo_read_close();
		$this->_fifo_write_close();
	}

	/**
	 * Run child process, then exit
	 */
	private function child() {
		$this->parent = false;
		if ($this->option_bool("nofork")) {
			$this->_fifos_close();
		} else {
			$this->_fifo_read_close();
			$this->_fifo_write();
		}
		$this->install_signals();
		$pid = $this->application->process->id();
		$this->send(array(
			$this->name => $pid
		));
		$this->application->logger->debug("Running {name} as part of {pid}", array(
			"name" => $this->name,
			"pid" => $pid
		));
		$result = call_user_func($this->method, $this);
		if ($result === "down") {
			$this->send(array(
				$this->name => "down"
			));
		} else {
			$this->send(array(
				$this->name => null
			));
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
	function done() {
		pcntl_signal_dispatch();
		if ($this->quitting) {
			return true;
		}
		$now = microtime(true);
		if ($this->last_tick === null || $now - $this->last_tick > 1.0) {
			$this->last_tick = $now;
			if (Process_Tools::process_code_changed($this->application)) {
				$this->warning("Code changed - exiting.");
				$this->quitting = true;
			}
			$nobj = gc_collect_cycles();
			if ($nobj > 0) {
				$this->log("Collected {nobj} object cycles", array(
					"nobj" => $nobj
				));
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
	function kill() {
		$this->quitting = true;
		$pid = zesk()->process->id();
		if ($this->parent) {
			$database = $this->_process_database();
			foreach ($database as $name => $pid) {
				if ($pid !== $pid) {
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
	private function shutdown_children(array $database) {
		foreach ($database as $name => $settings) {
			$pid = $status = null;
			extract($settings, EXTR_IF_EXISTS);
			if ($status === 'up' && $pid) {
				$this->application->logger->debug("Sending SIGINT to {pid}", array(
					'pid' => $pid
				));
				posix_kill($pid, SIGINT);
			}
		}
		while (count($database) > 1) {
			usleep(intval(0.1 * 1000000));
			foreach ($database as $name => $settings) {
				$pid = $status = null;
				extract($settings, EXTR_IF_EXISTS);
				if ($pid === $this->application->process->id()) {
					continue;
				}
				if ($status === 'down') {
					unset($database[$name]);
					continue;
				}
				$status = null;
				$result = pcntl_waitpid($pid, $status, WNOHANG);
				if ($result === -1) {
					$this->application->logger->error("pcntl_waitpid({pid}, {status}, WNOHANG) returned -1, child died? {name}", array(
						"name" => $name,
						"pid" => $pid,
						"status" => $status
					));
					unset($database[$name]);
				} else if ($result === 0) {
					$this->application->logger->debug("pcntl_waitpid({pid}, {status}, WNOHANG) returned 0, no child available. {name}.", array(
						"name" => $name,
						"pid" => $pid,
						"status" => $status
					));
					unset($database[$name]);
					continue;
				} else {
					if (pcntl_wifexited($status)) {
						unset($database[$name]);
						$this->application->logger->debug("pcntl_wifexited({status}) {pid} success {name}", array(
							"name" => $name,
							"pid" => $pid,
							"status" => $status
						));
					}
				}
			}
		}
	}

	/**
	 * Terminate this process.
	 * Nice way to do it.
	 */
	function terminate($message = null) {
		if ($this->quitting) {
			return;
		}
		if ($message) {
			$this->application->logger->notice($message);
		}
		$this->quitting = true;
		if ($this->parent) {
			$database = $this->_process_database();
			if (count($database) > 0) {
				$this->shutdown_children($database);
			} else {
				$this->application->logger->error("Database is empty on termination? ...");
			}
			usleep(0.1 * 1000000);
			$this->application->logger->debug("Deleting FIFO and database ...");
			unlink($this->fifo_path);
			$this->module->unlink_database();
		} else {
			$this->send();
			if ($this->has_option("terminate-wait")) {
				sleep($this->option_integer("terminate-wait", 1));
			}
			$this->application->logger->notice("Daemon child terminated ...");
			exit();
		}
	}

	/**
	 * Take a nap.
	 * I love naps.
	 */
	function sleep($seconds = 1.0) {
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
	function warning($message, array $args = array()) {
		$this->application->logger->warning($message, $args);
	}
}
