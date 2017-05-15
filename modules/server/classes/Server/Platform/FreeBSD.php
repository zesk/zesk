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
class Server_Platform_FreeBSD extends Server_Platform_UNIX {
	protected $root_user = "root";
	protected $root_group = "wheel";
	protected $command_locations = array(
		'pw' => '/usr/sbin',
		'sysctl' => '/sbin'
	);
	private static $users = array();
	private static $groups = array();
	
	/**
	 * @return Server_Packager
	 */
	protected function packager() {
		return new Server_Packager_PKG($this);
	}
	public function user($user) {
		if (array_key_exists($user, self::$users)) {
			return self::$users[$user];
		}
		try {
			$result = $this->exec_one("pw user show {0}", $user);
			// publish:*:1001:1001::0:0:Publish User:/publish:/usr/local/bin/bash
			return self::$users[$user] = arr::rekey(array(
				self::f_user_name,
				"x-password",
				self::f_user_id,
				self::f_user_group_id,
				"x-class",
				"x-change",
				"x-expire",
				self::f_user_full_name,
				self::f_user_home,
				self::f_user_shell
			), explode(":", $result, 9));
		} catch (Server_Exception $e) {
		}
		return null;
	}
	public function group($group) {
		if (array_key_exists($group, self::$groups)) {
			return self::$groups[$group];
		}
		try {
			$result = $this->exec_one("pw group show {0}", $group);
			// publish:*:1001:
			$data = arr::rekey(array(
				self::f_group_name,
				null,
				self::f_group_id,
				self::f_group_members
			), explode(":", $result, 4));
			$data['members'] = to_list($data['members']);
			return self::$groups[$group] = $data;
		} catch (Server_Exception $e) {
		}
		return null;
	}
	function user_create($user, $group, $home = null, $full_name = null, $shell = null, $uid = null) {
		if (!$this->group_exists($group)) {
			throw new Server_Exception_Group_NotFound($group);
		}
		$da = ($home !== null) ? " -d {2}" : "";
		$sa = ($shell !== null) ? " -s {3}" : "";
		$ua = ($uid !== null) ? " -u {4}" : "";
		$this->root_exec("pw user add {0} -g {1}$da$sa$ua", $user, $group, $home, $shell, $uid);
		return $this->user_id($user);
	}
	function user_delete($user) {
		if (!$this->user_exists($user)) {
			throw new Server_Exception_User_NotFound($user);
		}
		$force = " -r";
		$this->root_exec("pw user del {0}$force", $user);
		return true;
	}
	function group_create($group, $members = null, $gid = null) {
		$ma = "";
		if ($members !== null) {
			$members = arr::trim_clean(to_list($members, array()));
			if (count($members) > 0) {
				foreach ($members as $member) {
					if (!$this->group_exists($member)) {
						throw new Server_Exception_Group_NotFound($members, "When adding $group with members $members");
					}
				}
				$ma = " -m {1}";
				$members = implode(",", $members);
			}
			if ($gid !== null) {
				$ga = " -g {2}";
			}
		}
		$this->root_exec("pw group add {0}$ma$ga", $group, $members, $gid);
		return $this->group_id($group);
	}
	function group_delete($group) {
		if (!$this->group_exists($group)) {
			throw new Server_Exception_Group_NotFound($group);
		}
		$this->root_exec("pw group del {0}", $group);
		return true;
	}
	public function syslog_restart() {
		$this->root_exec("/etc/rc.d/syslogd restart");
	}
}
