<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/process/group.inc $
 * @author Kent M. Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2013, Market Acumen, Inc.
 * @package zesk
 * @subpackage kernel
 */

/**
 * The Unix platform abstraction
 *
 * @package zesk
 * @subpackage kernel
 */
class Process_Group implements Process_Interruptable {
	/**
	 * This process group name
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Current Process ID
	 *
	 * @var integer
	 */
	public $pid;
	/**
	 * Current parent process ID
	 *
	 * @var integer
	 */
	public $parent_pid;

	/**
	 * Array of child process IDs
	 *
	 * @var integer
	 */
	public $pids;

	/**
	 * Are we running?
	 *
	 * @var boolean
	 */
	public $running;

	/**
	 * Are we trying to exit?
	 *
	 * @var boolean
	 */
	private $exiting;

	/**
	 * The sleep interval, in milliseconds
	 *
	 * @var integer
	 */
	private $sleep_interval = 1;

	/**
	 * Timer of how long process has been running
	 *
	 * @var Timer
	 */
	public $timer;
	public $can_cancel;
	public $term_timeout;
	private $process_count;
	static $signals = array(
		SIGINT => "SIGINT",
		SIGCHLD => "SIGCHLD",
		SIGALRM => "SIGALRM",
		SIGTERM => "SIGTERM",
		SIGCHLD => "SIGCHLD",
		SIGHUP => "SIGHUP"
	);
	protected function __construct($name, $canCancel = true) {
		$this->name = $name;
		$this->pid = posix_getpid();
		$this->parent_pid = null;
		$this->pids = array();
		$this->exiting = false;
		$this->can_cancel = $canCancel;
		$this->term_timeout = 10;
		$this->process_count = 0;
		$this->timer = new Timer();

		$this->install_signals();
	}
	public function processCount() {
		return $this->process_count;
	}
	public function name() {
		return $this->name;
	}
	protected function install_signals() {
		if (function_exists("pcntl_signal")) {
			$callback = array(
				'Process_Group',
				'signal_handler'
			);
			pcntl_signal(SIGCHLD, $callback);
			pcntl_signal(SIGTERM, $callback);
			pcntl_signal(SIGINT, $callback);
			pcntl_signal(SIGALRM, $callback);

			register_shutdown_function(array(
				"Process_Group",
				"shutdown"
			));
		}
	}

	/**
	 * The unix signal handler for multi-process systems
	 *
	 * @param integer $signo
	 *        	The signal number to handle
	 */
	public static function signal_handler($signo) {
		// echo "Unix Signal Handler: $signo\n";
		$x = Process_Group::instance();
		if (!$x) {
			throw new Exception("No active process");
		}
		$signo = strval($signo);
		log::notice("Signal {signame} {signo} received", array(
			'signo' => $signo,
			'signame' => avalue(self::$signals, $signo, 'Unknown')
		));
		if (function_exists("pcntl_signal")) {
			switch ($signo) {
				case SIGINT:
					$x->terminate("Ctrl-C pressed");
					break;
				case SIGCHLD:
					$x->_childDied();
					break;
				case SIGALRM:
					break;
				case SIGTERM:
					$x->terminate("Termination signal received");
					break;
				case SIGHUP:
					$x->terminate("Hangup signal received");
					break;
				default:
					break;
			}
		}
	}
	public static function shutdown() {
		$g = Process_Group::instance();
		if ($g) {
			$g->terminated();
		}
	}

	/**
	 *
	 * @deprecated use instance
	 */
	static function master($name = false) {
		return self::instance($name);
	}

	/**
	 * Process_Group
	 *
	 * @param string $name
	 * @return Process_Group
	 */
	static function instance($name = false) {
		static $MasterProcessGroup = null;
		if ($MasterProcessGroup instanceof Process_Group) {
			return $MasterProcessGroup;
		}
		if ($name === false) {
			return false;
		}
		$x = new Process_Group($name);
		if ($x->anotherRunning()) {
			return false;
		}
		$MasterProcessGroup = $x;
		return $MasterProcessGroup;
	}
	function anotherRunning() {
		$pid = $this->_masterProcessID();
		if (!$pid) {
			return false;
		}
		if ($pid == $this->pid) {
			return false;
		}
		return true;
	}
	function isParent() {
		return $this->parent_pid === null;
	}
	private function _processPIDPath() {
		return path(zesk::get('Zesk::run_path'), $this->name . ".pid");
	}
	private function _writePIDFile() {
		$pid_file = $this->_processPIDPath();
		$pid_path = dirname($pid_file);
		if (!is_dir($pid_path)) {
			if (!mkdir($pid_path, 0777, true)) {
				throw new Exception_Directory_NotFound("$pid_path can not be created");
			}
		}
		if (file_exists($pid_file) && !is_writable($pid_file)) {
			throw new Exception_File_Permission($pid_file . " can not be written");
		}
		$f = fopen($this->_processPIDPath(), "w");
		if (!flock($f, LOCK_EX)) {
			fclose($f);
			return false;
		}
		fwrite($f, $this->pid);
		fclose($f);
		return true;
	}
	public function terminated() {
		$path = $this->_processPIDPath();
		if ($this->_masterProcessID(false) === $this->pid) {
			$this->log("Deleting {path}", array(
				'path' => $path
			));
			@unlink($path);
		} else {
			$this->log("Not master process");
		}
	}
	private function _masterProcessID($create = true) {
		$path = $this->_processPIDPath();
		if (!file_exists($path)) {
			return $this->_writePIDFile();
		}
		$x = intval(trim(file::contents($path, 0)));
		if ($x === $this->pid) {
			return $x;
		}
		if ($x && posix_kill($x, 0)) {
			return $x;
		}
		if (file_exists($path)) {
			@unlink($path);
		}
		if (!$create) {
			return null;
		}
		if (!$this->_writePIDFile()) {
			return false;
		}
		return $this->pid;
	}
	function setTerminateTimeout($v) {
		$this->term_timeout = intval($v);
	}
	function _pollChildren() {
		if (!function_exists("pcntl_waitpid")) {
			$this->log("!function_exists(\"pcntl_waitpid\")");
			return false;
		}
		foreach (array_keys($this->pids) as $apid) {
			$status = false;
			$pid = pcntl_waitpid($apid, $status, WNOHANG);
			if ($pid == -1) {
				unset($this->pids[$apid]);
				$this->warning("pcntl_waitpid returned -1 for {apid}, child died?", array(
					"apid" => $apid
				));
				return true;
			}
			if ($pid == 0) {
				$this->debug("{apid} still running", array(
					"apid" => $apid
				));
				continue;
			}
			if (pcntl_wifexited($status)) {
				if (isset($this->pids[$pid])) {
					$this->warning("Exited... {pid}", array(
						'pid' => $pid
					));
					unset($this->pids[$pid]);
				} else {
					$this->error("Process_Group::_pollChildren(): $pid not found in pids " . implode(',', array_keys($this->pids)));
				}
				return $pid;
			}
		}
		return true;
	}
	function _childDied() {
	}
	private function _terminate($signal, $reason) {
		if ($reason && $this->can_cancel) {
			if ($this->isParent()) {
				$this->log("INTERRUPTED: $reason");
			}
		}
		$this->warning("done()");
		$this->exiting = true;
		if (!$this->running) {
			$this->terminated();
		}
		if (!$this->isParent()) {
			posix_kill($this->parent_pid, $signal);
		}
	}
	function terminate($reason = false) {
		return $this->_terminate(SIGTERM, $reason);
	}
	function kill($reason = false) {
		return $this->_terminate(SIGINT, $reason);
	}
	function alarm($nSeconds) {
		if (!function_exists("pcntl_alarm")) {
			$this->log("No such function pcntl_alarm");
			return false;
		}
		return pcntl_alarm($nSeconds);
	}
	function done() {
		return $this->exiting;
	}
	function sleep($seconds = null) {
		$interval = max(min($seconds, $this->sleep_interval), 1);
		declare(ticks = 1) {
			$start = microtime(true);
			while (microtime(true) - $start < $seconds) {
				sleep($this->sleep_interval);
				if ($this->done()) {
					return false;
				}
			}
		}
		return true;
	}
	function pid() {
		return $this->pid;
	}
	private function _killChildren($violently = false) {
		if (!function_exists("posix_kill")) {
			return false;
		}
		if (!defined('SIGKILL')) {
			return false;
		}
		$signo = $violently ? SIGKILL : SIGTERM;
		foreach (array_keys($this->pids) as $pid) {
			$this->notice("Term signal to {pid}", array(
				"pid" => $pid
			));
			posix_kill($pid, $signo);
		}
		$this->_pollChildren();
		return count($this->pids);
	}
	private function fork() {
		$pid = pcntl_fork();
		if ($pid == -1) {
			return false;
		}
		if ($pid) {
			$this->pids[$pid] = true;
			$this->log("parent forked child $pid ($this->parent_pid)");
			return true;
		} else {
			$this->parent_pid = $this->pid;
			$this->pid = intval(posix_getpid());
			$this->timer = new Timer();
			$this->log("child process $this->pid");
			return $this->pid;
		}
	}
	function error($message, array $args = array()) {
		$this->log($message, $args, log::ERROR);
	}
	function notice($message, array $args = array()) {
		$this->log($message, $args, log::NOTICE);
	}
	function debug($message, array $args = array()) {
		$this->log($message, $args, log::DEBUG);
	}
	function warning($message, array $args = array()) {
		$this->log($message, $args, log::WARNING);
	}
	function log($message, array $args = array(), $level = null) {
		log::send("{process_group_name} ({process_group_pid}) $message", $args + array(
			"process_group_name" => $this->name,
			"process_group_pid" => $this->pid,
			"process_group_nchildren" => count($this->pids),
			"process_group_parent_pid" => $this->parent_pid,
			"process_group_elapsed" => $this->timer->elapsed()
		), $level);
	}
	function run($nPids = 1, $exitTime = false, $runOne = false) {
		$this->process_count = $nPids;

		$this->debug("Process_Group::run()");
		if ($this->parent_pid) {
			// Should never get here if no pcntl
			if (!posix_kill($this->parent_pid, 0)) {
				$this->debug("Parent {process_group_parent_pid} exited, so do we");
				return false;
			}
			if ($this->exiting) {
				$this->debug("Child pid of {process_group_parent_pid} exiting");
				return false;
			}
			if ($exitTime) {
				if ($this->timer->elapsed() > $exitTime) {
					$this->debug("Child pid of {process_group_parent_pid} exiting after {process_group_elapsed} seconds");
					return false;
				}
				return true;
			}
			$this->error("run($nPids): Can't run a child when parent is running ($this->parent_pid) and no exitTime");
			return false;
		}
		if ($this->anotherRunning()) {
			$this->log("run($nPids): Another process is running " . $this->_masterProcessID());
			return false;
		}
		// Parent process sits around and watches children
		$this->log("Parent PID # " . $this->pid());
		declare(ticks = 1) {
			while (!$this->exiting) {
				$this->running = true;
				if ($nPids == 0) {
					return true;
				}
				while (count($this->pids) < $nPids) {
					if ($this->done()) {
						return false;
					}
					$result = $this->fork();
					if ($result === false) {
						$this->error("run($nPids): Can not fork!");
						return false;
					}
					if ($result !== true) {
						// Child PID, run it
						$this->install_signals();
						$this->log("Child PID # " . $this->pid() . " running ...");
						return true;
					}
					usleep(500);
					if ($runOne) {
						break;
					}
				}
				if ($this->_pollChildren() === false) {
					$this->log("_pollChildren is false!!!!!");
					break;
				}
				if ($runOne) {
					return 0;
				}
				usleep(500000);
			}
		}
		$this->running = false;
		$this->warning("Master process starting exit...");
		$timer = new Timer();
		while ($timer->elapsed() < $this->term_timeout && $this->_killChildren() > 0) {
			if (count($this->pids) === 0) {
				break;
			}
			usleep(500000);
		}
		$this->_killChildren(true);
		$this->log("Master process ending after {process_group_elapsed) seconds to kill children ({process_group_nchildren}) children remaining) ...");
		$this->terminated();
		return false;
	}

	/**
	 *
	 * @var Application
	 */
	private $application = null;

	public function application(Application $set = null) {
		if ($set) {
			$this->application = $set;
			return $this;
		}
		return $this->application;
	}
}
