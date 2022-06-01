<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Server_Platform_Linux extends Server_Platform_Unix {
	protected $root_group = 'root';

	private static $users_loaded = false;

	private static $users = [];

	private static $groups_loaded = false;

	private static $groups = [];

	private function network_restart() {
		$this->verbose_log('Restarting network ...');
		return $this->root_exec('nohup ifup -a --force');
	}

	private function network_configure() {
		if ($this->configuration_files('etc/network', 'interfaces', '/etc/network/')) {
			return $this->network_restart();
		}
		return true;
	}

	protected function packager() {
		if ($this->has_yum()) {
			return new Server_Packager_YUM($this);
		}
		if ($this->has_apt()) {
			return new Server_Packager_APT($this);
		}
		return null;
	}

	protected function has_yum() {
		return $this->has_shell_program('yum');
	}

	protected function has_apt() {
		return $this->has_shell_program('apt-get');
	}

	protected function linux_remove_rc(array $services): void {
		foreach ($services as $service) {
			$this->verbose_log("Removing /etc/init.d/$service from any startup scripts ...");
			$this->root_exec('update-rc.d -f {0} remove', $service);
			$path = path('/etc/init.d', $service);
			if (is_executable($path)) {
				$this->verbose_log("Stopping $path ...");
				$this->root_exec("$path stop");
			}
			$this->owner($path, 'root', '-rw-r--r--');
		}
	}

	public function hook_configure_features(): void {
		$this->packager->install([
			'curl',
			'rsync',
			'unzip',
			'bzip2',
		]);
		$this->network_configure();
	}

	public function restart_service($name): void {
		$path = path('/etc/init.d', $name);
		if (!is_executable($path)) {
			throw new Exception_File_NotFound($path);
		}
		$this->exec("$path restart");
	}

	protected function restart_syslogd(): void {
		$this->restart_service('sysklogd');
	}

	public function user_home($user) {
		$user = $this->user($user);
		if (!$user) {
			throw new Server_Exception_User_NotFound($user);
		}
		return avalue($user, self::f_user_home, null);
	}

	public function user_create($user, $group, $home = null, $full_name = null, $shell = null, $uid = null) {
		if (!$this->group_exists($group)) {
			throw new Server_Exception_Group_NotFound($group);
		}
		$da = ($home !== null) ? ' -d {2}' : '';
		$sa = ($shell !== null) ? ' -s {3}' : '';
		$ua = ($uid !== null) ? ' -u {4}' : '';
		$this->root_exec("useradd -g {1}$da$sa$ua {0}", $user, $group, $home, $shell, $uid);
		return $this->user_id($user);
	}

	public function user_delete($user, $force = false) {
		if (!$this->user_exists($user)) {
			throw new Server_Exception_User_NotFound($user);
		}
		$force = $force ? '-f ' : '';
		$this->root_exec("userdel $force{0}", $user);
		return true;
	}

	public function group_create($group, $members = null, $gid = null) {
		$ma = '';
		if ($members !== null) {
			$members = ArrayTools::listTrimClean(to_list($members, []));
			if (count($members) > 0) {
				foreach ($members as $member) {
					if (!$this->group_exists($member)) {
						throw new Server_Exception_Group_NotFound($members, "When adding $group with members $members");
					}
				}
				$ma = ' -m {1}';
				$members = implode(',', $members);
			}
			if ($gid !== null) {
				$ga = ' -g {2}';
			}
		}
		$this->root_exec("groupadd {0}$ma$ga", $group, $members, $gid);
		return $this->group_id($group);
	}

	public function group_delete($group) {
		if (!$this->group_exists($group)) {
			throw new Server_Exception_Group_NotFound($group);
		}
		$this->root_exec('groupdel {0}', $group);
		return true;
	}

	public function group($group) {
		if (!self::$groups_loaded) {
			self::$groups = $this->_load_group_file('/etc/group');
			self::$groups_loaded = true;
		}
		return avalue(self::$groups, $group);
	}

	public function user($user) {
		if (!self::$users_loaded) {
			self::$users = $this->_load_user_file('/etc/passwd');
			self::$users_loaded = true;
		}
		return avalue(self::$users, $user);
	}
}
