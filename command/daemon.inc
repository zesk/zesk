<?php
/**
 * @copyright &copy; 2016 Market Acumen, Inc.
 */
use zesk\arr;
use zesk\PHP;
use zesk\JSON;
use zesk\Directory;
use zesk\Timestamp;
use zesk\Locale;
use zesk\Text;
use zesk\Exception_File_Permission;

/**
 * Run daemons associated with an application. Runs a continual process for each daemon hook encountered, and restarts daemons
 * as needed when they fail.
 *
 * @author kent
 * @category Management
 */
class Command_Daemon extends Command_Base implements Process_Interruptable {
	protected $help = "Run daemons associated with an application. Runs a continual process for each daemon hook encountered, and restarts daemons as needed when they fail. Individual control over selected processes by using --up --down, and --bounce.";
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
	 * Directory where run (PID, controller) files are found
	 *
	 * @var string
	 */
	protected $rundir = null;
	
	/**
	 * Am I the parent?
	 *
	 * @var boolean
	 */
	private $parent = true;
	
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
	 * log::debug for database file read/writes
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
		global $zesk;
		/* @var $zesk zesk\Kernel */
		
		zesk\PHP::requires('pcntl', true);
		
		$this->configure("daemon", true);
		if ($this->option_bool('debug-log')) {
			echo Text::format_pairs(arr::filter_prefix($zesk->configuration->to_array(), "log"));
		}
		$runpath = $zesk->paths->data("run");
		$this->rundir = path($runpath, 'daemon');
		Directory::depend($this->rundir, 0700);
		
		$this->fifo_path = path($this->rundir, "daemon-controller");
		
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
				__CLASS__,
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
	 * Send $signal to parent process, terminating all processes eventually.
	 *
	 * @return number
	 */
	public final function command_stop($signal = SIGTERM) {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		$database = $this->_process_database();
		if (count($database) === 0) {
			$zesk->logger->error("Not running.");
			return 1;
		}
		$signal_name = avalue(self::$signals, $signal, $signal);
		$me = avalue($database, 'me');
		if (!$me) {
			$changed = false;
			$zesk->logger->error("Parent process has been terminated - killing children");
			var_dump($database);
			foreach ($database as $name => $settings) {
				$pid = $status = $time = null;
				extract($settings, EXTR_IF_EXISTS);
				if ($status === "up") {
					if (!posix_kill($pid, $signal)) {
						$zesk->logger->notice("Dead process {name} ({pid})", compact("name", "pid"));
						unset($database[$name]);
						$changed = true;
					} else {
						$zesk->logger->notice("Sent {signal_name} to {name} ({pid})", compact("name", "pid"));
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
		$zesk->logger->notice("Sent {signal} to process {pid}", array(
			"pid" => $pid,
			"signal" => $signal_name
		));
		posix_kill($pid, $signal);
		return 0;
	}
	private function daemons() {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		$daemons = $zesk->hooks->find_all(array(
			"Application::daemon",
			"Module::daemon",
			"Object::daemon"
		));
		return $daemons;
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
		global $zesk;
		/* @var $zesk zesk\Kernel */
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
					$zesk->logger->notice("Matched process name {procname}", compact("procname"));
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
			$zesk->logger->error("Unknown process {name}", array(
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
		global $zesk;
		/* @var $zesk zesk\Kernel */
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
					$zesk->logger->error("Unable to kill process {name} ({pid})", array(
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
	private function _database_path() {
		return path($this->rundir, "daemon.db");
	}
	private function _unlock_database() {
		$path = $this->_database_path();
		flock($path, LOCK_UN);
	}
	private function _process_database(array $database = null) {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		$path = $this->_database_path();
		if ($database === null) {
			if (!is_file($path)) {
				return array();
			}
			$fp = fopen($path, "r");
			flock($fp, LOCK_SH);
			$database = unserialize(fread($fp, 1024 * 1024));
			if ($this->db_debug) {
				$zesk->logger->debug("Read database: {data}", array(
					'data' => JSON::encode($database)
				));
			}
			flock($fp, LOCK_UN);
			fclose($fp);
			return is_array($database) ? $database : array();
		} else {
			if (!is_file($path)) {
				if (count($database) === 0) {
					return array();
				}
				if (!file_put_contents($path, serialize($database))) {
					throw new Exception_File_Permission($path, "write");
				}
			} else {
				if (count($database) === 0) {
					unlink($path);
					return array();
				}
			}
			$fp = fopen($path, "a");
			flock($fp, LOCK_SH);
			ftruncate($fp, 0);
			fwrite($fp, serialize($database));
			flock($fp, LOCK_UN);
			fclose($fp);
			if ($this->db_debug) {
				$zesk->logger->debug("Wrote database: {data}", array(
					'data' => JSON::encode($database)
				));
			}
			return $database;
		}
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
	public static function signal_handler($signo) {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		// echo "Unix Signal Handler: $signo\n";
		$x = self::instance();
		if (!$x) {
			throw new Exception("No active process");
		}
		$zesk->logger->debug("Signal {signame} {signo} received", array(
			'signo' => $signo,
			'signame' => avalue(self::$signals, $signo, 'Unknown')
		));
		if (function_exists("pcntl_signal")) {
			switch ($signo) {
				case SIGINT:
					$x->terminate("Interrupt signal received");
					break;
				case SIGCHLD:
					$x->send(); // Wake up server
					break;
				case SIGALRM:
					break;
				case SIGTERM:
					$x->terminate("Termination signal received");
					break;
				case SIGHUP:
					if (!$x->option_bool("nohup")) {
						$x->terminate("Hangup signal received");
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
			//$zesk->logger->debug("stream_select timed out after $sec sec $usec usec");
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
		global $zesk;
		/* @var $zesk zesk\Kernel */
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
			$file = $zesk->logger->file();
			if (!$file) {
				$nohuplog = path($this->rundir, "daemon.log");
				$this->error("Writing log to $nohuplog");
				$zesk->logger->file($nohuplog);
			}
			$pid = $this->daemonize();
			if ($pid === 0) {
				/* We are the child */
			} else {
				$this->log("Launched daemon PID $pid\n");
				return 0;
			}
		}
		$database['me'] = array(
			'pid' => $zesk->process->id(),
			'status' => 'up',
			'time' => microtime(true)
		);
		$this->_process_database($database);
		$zesk->logger->notice("Daemon run successfully.");
		self::instance($this);
		
		zesk\PHP::feature("time_limit", $this->option_integer("time_limit", 0));
		$daemons = $this->daemons();
		$zesk->logger->debug("Daemons to run: " . implode(", ", $daemons));
		
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
		self::install_signals();
		$terminate_after = $this->option_integer('terminate-after', 0);
		$timer = new Timer();
		$alive_notices = 0;
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
					$zesk->logger->notice("Daemon is running at {date}", array(
						"date" => Timestamp::now()->format()
					));
				}
			} while (!$this->done());
		}
		
		return 0;
	}
	private function run_child($name) {
		global $zesk;
		
		$pid = $zesk->process->id();
		/* @var $zesk zesk\Kernel */
		$zesk->logger->debug("FORKING for process {name} me={pid}", array(
			"name" => $name,
			"pid" => $pid
		));
		$nofork = $this->option_bool("nofork");
		if ($nofork) {
			$zesk->logger->warning("Not forking for child process because of --nofork");
			$child = 0;
		} else {
			$child = pcntl_fork();
		}
		if ($child < 0) {
			$zesk->logger->error("Unable to fork to run {name}", array(
				"name" => $name
			));
			return null;
		} else if ($child === 0) {
			$this->application->hooks->call('pcntl_fork-child');
			$zesk->logger->notice("Running {name} as Process ID {pid}", array(
				"name" => $name,
				"pid" => $pid
			));
			$this->method = $name;
			$this->child();
			// ->child() exits process always - exit here for documentation
			exit(0);
		}
		$this->application->hooks->call('pcntl_fork-parent');
		
		$zesk->logger->debug("PARENT FORKED for process {name} me={pid} child={child}", array(
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
		global $zesk;
		/* @var $zesk zesk\Kernel */
		if ($this->debug) {
			$zesk->logger->debug("Server waiting for data in FIFO (timeout is {timeout} seconds)", array(
				"timeout" => $timeout
			));
		}
		$result = $this->read($timeout);
		if ($this->debug) {
			$zesk->logger->debug("Server read: {data}", array(
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
					$zesk->logger->error("Child sent name which isn't in our database? {name}", array(
						"name" => $name
					));
				} else if ($database[$name]['pid'] !== $pid) {
					$zesk->logger->error("Child sent PID which doesn't match our database (Child sent {childpid}, we have {pid}?", $database[$name] + array(
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
					$zesk->logger->error("Service {name} requested to go down", array(
						"name" => $name
					));
				}
			} else {
				$zesk->logger->debug("Unknown message from child: {child_pid}", array(
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
		global $zesk;
		/* @var $zesk zesk\Kernel */
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
					$zesk->logger->debug("Child process {name} {pid} exited with {pcntl_status} (result = {wait})", compact("name", "wait", "pcntl_status", "pid"));
					unset($database[$name]);
					$this->_process_database($database);
					continue;
				}
				if (posix_kill($pid, 0)) {
					if ($status === 'down') {
						$zesk->logger->debug("Sending TERM to {name} ({pid}) - want down", array(
							"pid" => $pid,
							"name" => $name
						));
						posix_kill($pid, SIGTERM);
						continue;
					}
					if ($this->debug) {
						$zesk->logger->debug("Checking {name} {pid}: Running", array(
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
					$zesk->logger->debug("{name} REMOVED {pid} NOT RUNNING {errno}: {strerror}", array(
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
			$zesk->logger->debug("Child process {wait} exited with {status}", compact("wait", "status"));
		}
	}
	
	/**
	 * Open write FIFO
	 *
	 * @throws Exception_File_Permission
	 */
	private function _fifo_write() {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		if (is_resource($this->fifo_w)) {
			return;
		}
		$this->fifo_w = fopen($this->fifo_path, "w");
		if (!$this->fifo_w) {
			$zesk->logger->error("Can not open fifo {fifo} for writing", array(
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
		global $zesk;
		/* @var $zesk zesk\Kernel */
		$this->fifo_r = fopen($this->fifo_path, "r+");
		if (!$this->fifo_r) {
			$zesk->logger->error("Can not open fifo {fifo} for reading", array(
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
		global $zesk;
		/* @var $zesk zesk\Kernel */
		$this->parent = false;
		if ($this->option_bool("nofork")) {
			$this->_fifos_close();
		} else {
			$this->_fifo_read_close();
			$this->_fifo_write();
		}
		self::install_signals();
		$pid = $zesk->process->id();
		$this->send(array(
			$this->method => $pid
		));
		$zesk->logger->debug("Running {name} as part of {pid}", array(
			"name" => $this->method,
			"pid" => $pid
		));
		$result = call_user_func($this->method, $this);
		if ($result === "down") {
			$this->send(array(
				$this->method => "down"
			));
		} else {
			$this->send(array(
				$this->method => null
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
			if (Process_Tools::process_code_changed()) {
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
		global $zesk;
		/* @var $zesk zesk\Kernel */
		foreach ($database as $name => $settings) {
			$pid = $status = null;
			extract($settings, EXTR_IF_EXISTS);
			if ($status === 'up' && $pid) {
				$zesk->logger->debug("Sending SIGINT to {pid}", array(
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
				if ($pid === $zesk->process->id()) {
					continue;
				}
				if ($status === 'down') {
					unset($database[$name]);
					continue;
				}
				$status = null;
				$result = pcntl_waitpid($pid, $status, WNOHANG);
				if ($result === -1) {
					$zesk->logger->error("pcntl_waitpid({pid}, {status}, WNOHANG) returned -1, child died? {name}", array(
						"name" => $name,
						"pid" => $pid,
						"status" => $status
					));
					unset($database[$name]);
				} else if ($result === 0) {
					$zesk->logger->debug("pcntl_waitpid({pid}, {status}, WNOHANG) returned 0, no child available. {name}.", array(
						"name" => $name,
						"pid" => $pid,
						"status" => $status
					));
					unset($database[$name]);
					continue;
				} else {
					if (pcntl_wifexited($status)) {
						unset($database[$name]);
						log::debug("pcntl_wifexited({status}) {pid} success {name}", array(
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
		global $zesk;
		/* @var $zesk zesk\Kernel */
		if ($this->quitting) {
			return;
		}
		if ($message) {
			$zesk->logger->notice($message);
		}
		$this->quitting = true;
		if ($this->parent) {
			$database = $this->_process_database();
			if (count($database) > 0) {
				$this->shutdown_children($database);
			} else {
				$zesk->logger->error("Database is empty on termination? ...");
			}
			usleep(0.1 * 1000000);
			$zesk->logger->debug("Deleting FIFO and database ...");
			unlink($this->fifo_path);
			unlink($this->_database_path());
		} else {
			$this->send();
			if ($this->has_option("terminate-wait")) {
				sleep($this->option_integer("terminate-wait", 1));
			}
			$zesk->logger->notice("Daemon child terminated ...");
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
		global $zesk;
		/* @var $zesk zesk\Kernel */
		$zesk->logger->warning($message, $args);
	}
}
