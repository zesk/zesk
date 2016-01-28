<?php
/**
 * This code manages daemon processes and related stuff
 * @deprecated $URL: https://code.marketacumen.com/zesk/trunk/modules/zesk_legacy/process-legacy.inc $
 * @package zesk
 * @subpackage system
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 */

if (!defined("PID_FILE")) {
	log::warning("Define PID_FILE before including " . __FILE__);
	return;
}

zesk::deprecated();
function is_process_done() {
	global $process_done;

	return $process_done;
}
function process_is_done($value = true) {
	global $process_done;

	$process_done = $value;
}

if (!defined("SIGSTKFLT"))
	define('SIGSTKFLT', 9999);
if (!defined("SIGPWR"))
	define('SIGPWR', 9998);
function unix_signal_handler($signo) {
	static $sigmap = array(
		SIGHUP => "HUP",
		SIGINT => "INT",
		SIGQUIT => "QUIT",
		SIGILL => "ILL",
		SIGTRAP => "TRAP",
		SIGABRT => "ABRT",
		SIGIOT => "IOT",
		SIGBUS => "BUS",
		SIGFPE => "FPE",
		SIGKILL => "KILL",
		SIGSEGV => "SEGV",
		SIGPIPE => "PIPE",
		SIGALRM => "ALRM",
		SIGTERM => "TERM",
		SIGSTKFLT => "STKFLT",
		SIGCHLD => "CHLD",
		SIGCONT => "CONT",
		SIGSTOP => "STOP",
		SIGTSTP => "TSTP",
		SIGTTIN => "TTIN",
		SIGTTOU => "TTOU",
		SIGURG => "URG",
		SIGXCPU => "XCPU",
		SIGXFSZ => "XFSZ",
		SIGVTALRM => "VTALRM",
		SIGPROF => "PROF",
		SIGWINCH => "WINCH",
		SIGIO => "IO",
		SIGPWR => "PWR",
		SIGSYS => "SYS"
	);
	global $log_hup_function;

	switch ($signo) {
		case SIGHUP:
			{
				log::warning("Received signal HUP");
				$log_hup_function = log::hup_function();
				if (!empty($log_hup_function)) {
					$log_hup_function();
				} else {
					process_is_done();
				}
				break;
			}
		case SIGINT:
			log::error("SIGINT received: Ctrl-C pressed");
			process_is_done();
			break;
		case SIGCHLD:
			log::error("SIGCHLD received: Child died");
			break;
		case SIGTERM:
			log::error("SIGTERM received: Termination signal received");
			process_is_done();
			break;
		case SIGALRM:
			break;
		default:
			{
				log::error("Received signal {signal} ({signo})", array(
					"signal" => avalue($sigmap, $signo, "-unknown-"),
					"signo" => $signo
				));
				process_is_done();
				break;
			}
	}
}
function load_pid_file() {
	if (!file_exists(PID_FILE)) {
		return false;
	}
	return intval(file_get_contents(PID_FILE));
}
function store_pid_file() {
	$pid = zesk::pid();
	file_put_contents(PID_FILE, "$pid");
}
function delete_pid_file() {
	@unlink(PID_FILE);
}
function process_already_running() {
	$running_pid = load_pid_file();
	if ($running_pid < 0) {
		return false;
	}
	if (!$running_pid) {
		return false;
	}
	if ($running_pid == zesk::pid()) {
		return false;
	}
	if (posix_kill($running_pid, 0)) {
		return true;
	}
	return false;
}

if (false) {
	function pcntl_signal($signo, $function) {
	}
	;
	/**
	 * Returns 0 as child process, child pid as parent, and -1 on failure
	 * @return integer
	 */
	function pcntl_fork() {
	}
	;
}
function unix_signal_setup() {
	process_is_done(false);

	if (function_exists("pcntl_signal")) {
		pcntl_signal(SIGTERM, "unix_signal_handler");
		pcntl_signal(SIGHUP, "unix_signal_handler");
		pcntl_signal(SIGQUIT, "unix_signal_handler");
		pcntl_signal(SIGINT, "unix_signal_handler");
		pcntl_signal(SIGCHLD, "unix_signal_handler");
		pcntl_signal(SIGALRM, "unix_signal_handler");

		log::notice("Signals installed");
	} else {
		log::warning("No signals installed");
	}
}

