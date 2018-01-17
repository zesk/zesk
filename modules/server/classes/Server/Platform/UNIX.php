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
abstract class Server_Platform_UNIX extends Server_Platform {
	protected $root_user = "root";
	protected $uid = null;
	private static $users = array();
	private static $groups = array();
	const f_user_name = "name";
	const f_user_full_name = "full_name";
	const f_user_id = "uid";
	const f_user_home = "home";
	const f_user_shell = "shell";
	const f_user_group_id = "gid";
	const f_group_name = "name";
	const f_group_id = "gid";
	const f_group_members = "members";
	public function has_shell_program($command) {
		return $this->application->paths->which($command) === null ? false : true;
	}
	public function user_current() {
		return trim($this->exec_one("id -un"));
	}
	public function user_home($user) {
		$data = $this->user($user);
		if (!$data) {
			throw new Server_Exception_User_NotFound($user);
		}
		return $data[self::f_user_home];
	}
	public function user_id($user) {
		$data = $this->user($user);
		if (!$data) {
			throw new Server_Exception_User_NotFound($user);
		}
		return $data[self::f_user_id];
	}
	public function user_group_id($user) {
		$data = $this->user($user);
		if (!$data) {
			throw new Server_Exception_User_NotFound($user);
		}
		return $data[self::f_user_group_id];
	}
	public function user_exists($user) {
		return $this->user($user) !== null;
	}
	public function group_exists($group) {
		return $this->group($group) !== null;
	}
	public function group_id($group) {
		$data = $this->group($group);
		if (!$data) {
			throw new Server_Exception_Group_NotFound($group);
		}
		return $data[self::f_group_id];
	}
	public function group_members($group) {
		$data = $this->group($group);
		if (!$data) {
			throw new Server_Exception_Group_NotFound($group);
		}
		return $data[self::f_group_members];
	}
	public function is_root() {
		if ($this->uid !== null) {
			return $this->uid;
		}
		if (function_exists('posix_getuid')) {
			$this->uid = posix_getuid();
		} else {
			if ($this->uid === null) {
				$this->uid = intval($this->exec_one('id -u'));
			}
		}
		return $this->uid;
	}
	public function process_is_running($path) {
		$processes = $this->processes_running();
		foreach ($processes as $process) {
			$command = null;
			list($command) = $process['command'];
			if ($command === $path) {
				return true;
			}
		}
		return false;
	}
	public function ps_heading_aliases() {
		return array();
	}
	public function processes_running() {
		$lines = $this->exec("ps aux");
		$processes = array();
		$line = array_shift($lines);
		$headings = explode(" ", trim(preg_replace('/\s+/', ' ', $line)));
		$heading_aliases = $this->ps_heading_aliases();
		foreach ($headings as $index => $heading) {
			$heading = strtolower($heading);
			$heading[$index] = avalue($heading_aliases, $heading, $heading);
		}
		
		foreach ($lines as $line) {
			$items = explode(" ", trim(preg_replace('/\s+/', ' ', $line)), count($headings)) + array_fill(0, count($headings));
			$row = array();
			foreach ($items as $index => $value) {
				$name = $headings[$index];
				if ($name === "command") {
					$value = $args = null;
					list($value, $args) = pair($value, " ", $value, null);
					$row['arguments'] = $args;
				}
				$row[$name] = $value;
			}
			$processes[] = $row;
		}
		return $processes;
	}
	protected function _load_group_file($file = '/etc/group') {
		$groups = array();
		foreach (File::lines($file) as $line) {
			list($line) = pair($line, "#", $line, null);
			$line = trim($line);
			if (empty($line)) {
				continue;
			}
			$data = ArrayTools::rekey(array(
				self::f_group_name,
				null,
				self::f_group_id,
				self::f_group_members
			), null, explode(":", $line, 4) + array_fill(0, 4));
			$groups[$data[self::f_group_name]] = $data;
		}
		return $groups;
	}
	protected function _load_user_file($file = '/etc/passwd') {
		$users = array();
		$columns = array(
			self::f_user_name,
			"x-password",
			self::f_user_id,
			self::f_user_group_id,
			self::f_user_full_name,
			self::f_user_home,
			self::f_user_shell
		);
		$n_columns = count($columns);
		foreach (File::lines($file) as $line) {
			// publish:x:1001:1000:Publish User:/publish:/bin/bash
			list($line) = pair($line, "#", $line, null);
			$line = trim($line);
			if (empty($line)) {
				continue;
			}
			$data = ArrayTools::rekey($columns, explode(":", $line, $n_columns) + array_fill(0, $n_columns));
			$users[$data[self::f_user_name]] = $data;
		}
		return $users;
	}
	function login_script_install($user, $name, $command) {
		throw new Exception_Unimplemented(__CLASS__ . '::' . __METHOD__);
	}
	function login_script_installed($user, $name) {
		throw new Exception_Unimplemented(__CLASS__ . '::' . __METHOD__);
	}
	function login_script_run($user, $name) {
		throw new Exception_Unimplemented(__CLASS__ . '::' . __METHOD__);
	}
	function login_script_uninstall($user, $name) {
		throw new Exception_Unimplemented(__CLASS__ . '::' . __METHOD__);
	}
}

