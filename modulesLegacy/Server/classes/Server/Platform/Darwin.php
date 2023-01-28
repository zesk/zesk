<?php declare(strict_types=1);
/**
 *
 */
namespace Server\classes\Server\Platform;

use Server\classes\Server\Exception\Group\Server_Exception_Group_Create;
use Server\classes\Server\Exception\Group\Server_Exception_Group_NotFound;
use Server\classes\Server\Exception\Server_Exception_Command;
use Server\classes\Server\Exception\User\Server_Exception_User_Create;
use Server\classes\Server\Exception\User\Server_Exception_User_NotFound;
use Server\classes\Server\Platform\Server_Platform_UNIX;
use Server\classes\Server\Server_Exception;
use Server\classes\Server\Server_Platform;
use zesk\ArrayTools;
use zesk\Directory;
use zesk\Exception;
use zesk\Exception_Directory_NotFound;
use zesk\Exception_File_Permission;
use zesk\File;
use zesk\multitype;
use zesk\StringTools;
use const zesk\options;

/**
 *
 * @author kent
 *
 */
class Server_Platform_Darwin extends Server_Platform_UNIX {
	/**
	 * Root group
	 *
	 * @var string
	 */
	protected $root_group = 'wheel';

	/**
	 * User cache
	 *
	 * @var array of array
	 */
	private static $users = [];

	/**
	 * Group cache
	 *
	 * @var array of array
	 */
	private static $groups = [];

	/**
	 * Darwin keys for mapping to standard Server_Platform_UNIX keys
	 *
	 * @var string
	 */
	public const darwin_user_shell = 'UserShell';

	public const darwin_user_id = 'UniqueID';

	public const darwin_user_group_id = 'PrimaryGroupID';

	public const darwin_user_full_name = 'RealName';

	public const darwin_user_home = 'NFSHomeDirectory';

	public const darwin_group_id = 'PrimaryGroupID';

	public const darwin_group_name = 'RecordName';

	public const darwin_group_members = 'GroupMembership';

	/**
	 * Name of the root volume - cached
	 *
	 * @var string
	 */
	private $root_volume_name = null;

	/**
	 * Directory in user home directory for storing per-user scripts
	 *
	 * @var string
	 */
	public const userdir_launch_agents = 'Library/LaunchAgents';

	/**
	 * File mode for the directory
	 *
	 * @var integer
	 */
	public const mode_userdir_launch_agents = 0o755;

	/**
	 * (non-PHPdoc)
	 *
	 * @see Server_Platform::packager()
	 */
	protected function packager() {
		if ($this->has_shell_command('brew')) {
			return $this->application->objects->factory(__NAMESPACE__ . '\\Server_Packager_Brew', $this);
		}
		if ($this->has_shell_command('macports')) {
			return $this->application->objects->factory(__NAMESPACE__ . '\\Server_Packager_MacPORTS', $this);
		}
		if ($this->has_shell_command('fink')) {
			return $this->application->objects->factory(__NAMESPACE__ . '\\Server_Packager_Fink', $this);
		}
		return null;
	}

	/**
	 * Parse the output of dscl command
	 *
	 * @param array $lines
	 * @return array
	 */
	private static function _parse_dscl_output(array $lines) {
		$pairs = [];
		$last_key = $last_value = null;
		$is_xml = false;
		foreach ($lines as $line) {
			if ($is_xml) {
				if ($line === '') {
					$pairs[$last_key] = $last_value;
					$is_xml = false;
				} else {
					$last_value .= $line;
				}

				continue;
			}
			if (substr($line, 0, 1) === ' ') {
				if (StringTools::begins($line, ' <?xml')) {
					$is_xml = true;
					$last_value = substr($line, 1);
				} elseif ($last_key) {
					$pairs[$last_key] = substr($line, 1);
				}

				continue;
			}
			$last_key = null;
			[$name, $value] = reversePair($line, ':', $line, null);
			$value = trim($value);
			if ($value === '') {
				$last_key = $name;
				$last_value = '';
			} elseif ($name === 'No such key') {
				continue;
			} else {
				$pairs[$name] = ltrim($value);
			}
		}
		if ($last_key !== null) {
			$pairs[$last_key] = $last_value;
			$last_key = null;
		}
		return $pairs;
	}

	/**
	 * Convert Darwin user output (dscl) to standard (UNIX)
	 *
	 * @return multitype:string
	 */
	private function darwin_user_map() {
		return [
			self::darwin_user_group_id => self::f_user_group_id,
			self::darwin_user_full_name => self::f_user_full_name,
			self::darwin_user_id => self::f_user_id,
			self::darwin_user_shell => self::f_user_shell,
			self::darwin_user_home => self::f_user_home,
		];
	}

	/**
	 * Convert Darwin group output (dscl) to standard (UNIX)
	 *
	 * @return multitype:string
	 */
	private function darwin_group_map() {
		return [
			self::darwin_group_id => self::f_group_id,
			self::darwin_group_name => self::f_group_name,
			self::darwin_group_members => self::f_group_members,
		];
	}

	/**
	 * Retrieve a user settings (and cache it)
	 *
	 * @see Server_Platform::user()
	 * @return array
	 */
	public function user($user) {
		if (array_key_exists($user, self::$users)) {
			return self::$users[$user];
		}
		$map = self::darwin_user_map();

		try {
			$result = $this->exec('dscl . -read /Users/{0} ' . implode(' ', array_keys($map)), $user);
			$result = self::_parse_dscl_output($result);
			return self::$users[$user] = ArrayTools::keysMap($result, $map);
		} catch (Server_Exception $e) {
		}
		return null;
	}

	/**
	 * Retrieve a group settings (and cache it)
	 *
	 * @see Server_Platform::group()
	 * @return array
	 */
	public function group($group) {
		if (array_key_exists($group, self::$groups)) {
			return self::$groups[$group];
		}
		/*
		 * AppleMetaNodeLocation: /Local/Default GeneratedUID: 4DCB8566-BB8F-4EF5-AAEE-CE0162E7D07E
		 * Password: * PrimaryGroupID: 1001 RecordName: kent RecordType: dsRecTypeStandard:Groups
		 */
		$map = self::darwin_group_map();

		try {
			$result = $this->exec('dscl . -read /Groups/{0} ' . implode(' ', array_keys($map)), $group);
			$data = ArrayTools::keysMap(self::_parse_dscl_output($result), $map);
			$data[self::f_group_members] = to_list($data[self::f_group_members], [], ' ');
			return self::$groups[$group] = $data;
		} catch (Server_Exception $e) {
		}
		return null;
	}

	/**
	 *
	 * @see Server_Platform::user_create()
	 */
	public function user_create($user, $group, $home = null, $options = null): void {
		$options = ArrayTools::filter(is_array($options) ? $options : [], [
			self::f_user_shell,
			self::f_user_full_name,
			self::f_user_id,
		]);
		if (!$this->group_exists($group)) {
			throw new Server_Exception_Group_NotFound($group);
		}
		$params = compact('user', 'group', 'home') + options;

		$shell = $full_name = $uid = null;
		extract($options, EXTR_IF_EXISTS);

		try {
			$this->exec('dscl . -create /Users/{0}', $user);
		} catch (Server_Exception_Command $e) {
			throw new Server_Exception_User_Create($user, $params);
		}
		$dscl_items = [];
		$dscl_items['PrimaryGroupID'] = $this->group_id($group);
		if ($home !== null) {
			$dscl_items[self::darwin_home] = $home;
		}
		if ($shell !== null) {
			$dscl_items[self::darwin_shell] = $shell;
		}
		if ($full_name !== null) {
			$dscl_items[self::darwin_full_name] = $full_name;
		}
		if ($uid !== null) {
			$dscl_items[self::darwin_uid] = $uid;
		}
		$ex = null;

		try {
			foreach ($dscl_items as $k => $v) {
				$this->exec('dscl . -create /Users/{0} {1} {2}', $user, $k, $v);
			}
		} catch (Server_Exception_Command $e) {
			$ex = $e;
		}

		try {
			$this->exec('dscl . -delete /Users/{0}', $user);
		} catch (Exception $e) {
		}

		throw new Server_Exception_User_Create($user, $params, null, null, $ex);
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Server_Platform::group_create()
	 */
	public function group_create($group, $members = null, $options = null): void {
		$options = ArrayTools::filter(is_array($options) ? $options : [], 'gid');
		$params = [
			'members' => to_list($members),
		] + ArrayTools::filter($options, 'gid');

		try {
			$this->exec('dscl . -create /Groups/{0}', $group);
		} catch (Server_Exception_Command $e) {
			throw new Server_Exception_Group_Create($group, $params);
		}
		$dscl_items = $params;
		$dscl_items[self::darwin_group_name] = $group;
		$gid = $params['gid'] ?? null;
		if ($gid !== null) {
			$dscl_items[self::darwin_group_id] = $gid;
		}
		$ex = null;

		try {
			foreach ($dscl_items as $k => $v) {
				$this->exec('dscl . -create /Groups/{0} {1} {2}', $group, $k, $v);
			}
			if ($members !== null) {
				$members = to_list($members, []);
				foreach ($members as $member) {
					$this->exec('dscl . -append /Groups/{0} {1} {2}', $group, self::darwin_group_members, $member);
				}
			}
		} catch (Server_Exception_Command $e) {
			$ex = $e;
		}

		try {
			$this->exec('dscl . -delete /Groups/{0}', $group);
		} catch (Exception $e) {
		}

		throw new Server_Exception_Group_Create($group, $params, null, null, $ex);
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Server_Platform::group_delete()
	 */
	public function group_delete($group) {
		if (!$this->group_exists($group)) {
			throw new Server_Exception_Group_NotFound($group);
		}
		$this->exec('dscl . -delete /Groups/{0}', $group);
		return true;
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Server_Platform::user_delete()
	 */
	public function user_delete($user) {
		if (!$this->user_exists($user)) {
			throw new Server_Exception_User_NotFound($user);
		}
		$this->exec('dscl . -delete /Users/{0}', $user);
		return true;
	}

	/**
	 * Retrieve the short name for a volume
	 *
	 * Caches the root volume which is a symlink to /
	 *
	 * @see Server_Platform::volume_short_name()
	 */
	public function volume_short_name($path) {
		if ($path === '/') {
			if ($this->root_volume_name !== null) {
				return $this->root_volume_name;
			}
			foreach (Directory::ls('/Volumes', null, true) as $path) {
				if (!is_link($path)) {
					continue;
				}
				$link = readlink($path);
				if ($link === '/') {
					return $this->root_volume_name = basename($path);
				}
			}
			return $this->root_volume_name = '/';
		}
		return basename($path);
	}

	/**
	 * Retrieve a user's directory, and ensure it exists
	 *
	 * @param string $user
	 * @param string $user_dir
	 * @throws Exception_Directory_NotFound
	 * @return string Full path of user's directory
	 */
	private function _user_directory($user, $user_dir, $create = false, $mode = null) {
		$path = $this->user_home($user);
		if (!is_dir($path)) {
			throw new Exception_Directory_NotFound($path, 'Home directory for {user} does not exist: {path}', [
				'user' => $user,
				'path' => $path,
			]);
		}
		$path = path($path, $user_dir);
		if (!is_dir($path) && !$create) {
			throw new Exception_Directory_NotFound($path, 'Directory for {user} does not exist: {path}', [
				'user' => $user,
				'path' => $path,
			]);
		}
		Directory::depend($path, $mode);
		return $path;
	}

	private function _userdir_launch_agents($user, $create = false) {
		return $this->_user_directory($user, self::userdir_launch_agents, $create, self::mode_userdir_launch_agents);
	}

	/**
	 * Retrieve the login script path for a login script
	 *
	 * @param string $user
	 * @param string $name
	 * @return string
	 */
	private function _login_script_path($user, $name) {
		$path = $this->_userdir_launch_agents($user, true);
		$name = File::name_clean($name);
		$name = 'com.zesk.' . $name;

		return path($path, $name . '.plist');
	}

	/**
	 * Return XML for the login script
	 *
	 * @param string $name
	 * @param string $command
	 * @return string
	 */
	private static function _login_script_contents($name, $command) {
		$contents = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<!DOCTYPE plist PUBLIC \"-//Apple Computer//DTD PLIST 1.0//EN\" \"http://www.apple.com/DTDs/PropertyList-1.0.dtd\">\n";
		$contents .= '<plist version="1.0"><dict><key>Label</key><string>{name}</string><key>Program</key><string>{command}</string><key>RunAtLoad</key><true/></dict></plist>';

		return map($contents, [
			'name' => $name,
			'command' => $command,
		]);
	}

	/**
	 * Is a login script installed for this user?
	 *
	 * @see Server_Platform::login_script_installed()
	 * @return boolean
	 */
	public function login_script_installed($user, $name) {
		$path = $this->_login_script_path($user, $name);
		return file_exists($path);
	}

	/**
	 * Install command to run at login for user
	 *
	 * @see Server_Platform::login_script_install()
	 * @throws Exception_File_Permission
	 * @throws Exception_Directory_NotFound
	 * @return boolean
	 */
	public function login_script_install($user, $name, $command) {
		$filename = $this->_login_script_path($user, $name);

		$path = $this->_userdir_launch_agents($user);
		$name = File::name_clean($name);
		$name = 'com.zesk.' . $name;

		$filename = path($path, $name . '.plist');

		$contents = self::_login_script_contents($name, $command);

		if (file_exists($filename)) {
			$old_contents = file_get_contents($filename);
			if ($old_contents === $contents) {
				$this->application->logger->debug('{class}::{method}: File {filename} is unchanged', [
					'class' => __CLASS__,
					'method' => __METHOD__,
					'filename' => $filename,
				]);
				return true;
			}
			$this->application->logger->debug('{class}::{method}: File {filename} will be updated to new version', [
				'class' => __CLASS__,
				'method' => __METHOD__,
				'filename' => $filename,
			]);
		} else {
			$this->application->logger->debug('{class}::{method}: File {filename} will be created', [
				'class' => __CLASS__,
				'method' => __METHOD__,
				'filename' => $filename,
			]);
		}
		File::put($filename, $contents);
		$this->exec('laumchctl load -D user {0}', $path);

		return true;
	}

	/**
	 * Run the login script now
	 * @see Server_Platform::login_script_run()
	 * @return
	 */
	public function login_script_run($user, $name) {
		$name = 'com.zesk.' . $name;
		$result = $this->exec('launchctl start {0}', $name);
		return $result;
	}

	public function login_script_uninstall($user, $name) {
		$path = $this->_userdir_launch_agents($user);
		$name = File::name_clean($name);
		$name = 'com.zesk.' . $name;

		$filename = path($path, $name . '.plist');

		return File::unlink($filename);
	}
}
/*
Leopard

http://osxdaily.com/2007/10/29/how-to-add-a-user-from-the-os-x-command-line-works-with-leopard/

dscl . -create /Users/toddharris

Create and set the shell property to bash.
dscl . -create /Users/toddharris UserShell /bin/bash

Create and set the user's full name.
dscl . -create /Users/toddharris RealName "Dr. Todd Harris"

Create and set the user's ID.
dscl . -create /Users/toddharris UniqueID 503

Create and set the user's group ID property.
dscl . -create /Users/toddharris PrimaryGroupID 1000

Create and set the user home directory.
dscl . -create /Users/toddharris NFSHomeDirectory /Local/Users/toddharris

Set the password.
dscl . -passwd /Users/toddharris PASSWORD

If you would like Dr. Harris to be able to perform administrative functions:
dscl . -append /Groups/admin GroupMembership toddharris

*/
