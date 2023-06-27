<?php
declare(strict_types=1);
/**
 * Integration with operating system. Host name, process ID, system services, load averages, volume info.
 *
 * @package zesk
 * @subpackage default
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk;

use Psr\Cache\InvalidArgumentException;
use zesk\Exception\CommandFailed;
use zesk\Exception\FileNotFound;
use zesk\Exception\FilePermission;

/**
 * @author kent
 */
class System
{
	public const BINARY_UPTIME = '/usr/bin/uptime';

	/**
	 * Field returned in users array
	 * @see self::users()
	 */
	public const USER_FIELD_USER_ID = 'uid';

	/**
	 * Field returned in users array
	 *
	 * @see self::users()
	 */
	public const USER_FIELD_GROUP_ID = 'gid';

	/**
	 * Field returned in users array
	 *
	 * @see self::users()
	 */
	public const USER_FIELD_NAME = 'user';

	/**
	 * Field returned in users array
	 *
	 * @see self::users()
	 */
	public const USER_FIELD_PASSWORD = 'masked';

	/**
	 * Default password file for user database
	 */
	public const DEFAULT_USERS_FILE = '/etc/passwd';

	/**
	 *
	 * @return string
	 */
	public static function uname(): string
	{
		return php_uname('n');
	}

	/**
	 * Get current process ID
	 *
	 * @return int
	 */
	public static function processId(): int
	{
		return getmypid();
	}

	/**
	 * Load IP addresses for this system
	 *
	 * @return array of interface => $ip
	 */
	public static function ipAddresses(Application $application): array
	{
		$interfaces = self::iffilter(self::ifconfig($application), ['inet', 'ether']);
		$ips = [];
		foreach ($interfaces as $interface => $values) {
			if (array_key_exists('inet', $values)) {
				$ip = ArrayTools::first(array_keys($values['inet']));
				if (IPv4::valid($ip)) {
					$ips[$interface] = $ip;
				}
			}
		}
		return $ips;
	}

	/**
	 * Load MAC addresses for this system
	 *
	 * @return array of interface => $ip
	 */
	public static function macAddresses(Application $application): array
	{
		$interfaces = self::ifconfig($application);
		$macs = [];
		foreach ($interfaces as $interface => $values) {
			if (array_key_exists('ether', $values)) {
				$mac = array_keys($values['ether'])[0] ?? null;
				$macs[$interface] = $mac;
			}
		}
		return $macs;
	}

	/**
	 * @param string $userFile
	 * @return array
	 * @throws FileNotFound
	 * @throws FilePermission
	 */
	public static function users(string $userFile = ''): array
	{
		if ($userFile === '') {
			$userFile = self::DEFAULT_USERS_FILE;
		}
		if (!is_file($userFile)) {
			throw new FileNotFound($userFile);
		}
		if (!is_readable($userFile)) {
			throw new FilePermission($userFile);
		}
		$result = [];
		foreach (File::lines($userFile) as $line) {
			$line = StringTools::left($line, '#');
			$parts = explode(':', $line);
			if (count($parts) >= 4) {
				$user[self::USER_FIELD_NAME] = $parts[0];
				$user[self::USER_FIELD_PASSWORD] = $parts[1];
				$user[self::USER_FIELD_USER_ID] = intval($parts[2]);
				$user[self::USER_FIELD_GROUP_ID] = intval($parts[3]);

				$result[$user[self::USER_FIELD_USER_ID]] = $user;
			}
		}
		return $result;
	}

	/**
	 * Filter ifconfig results to find interfaces with specific subkey(s)
	 *
	 * @param array $results
	 * @param array|string|null $filter
	 * @return array
	 */
	public static function iffilter(array $results, array|string $filter = null): array
	{
		$output = [];
		foreach ($results as $interface => $values) {
			if (is_array($values)) {
				if (ArrayTools::hasAnyKey($values, $filter)) {
					$output[$interface] = $values;
				}
			}
		}
		return $output;
	}

	/**
	 * Run ifconfig configuration utility and parse results
	 *
	 * @param Application $application
	 * @return array
	 */
	public static function ifconfig(Application $application): array
	{
		$command = null;
		$cache = null;

		try {
			$cache = $application->cacheItemPool()->getItem(__METHOD__);
			if ($cache->isHit()) {
				$command = $cache->get();
			}
		} catch (InvalidArgumentException) {
		}

		try {
			if (!$command) {
				$command = $application->process->execute('ifconfig');
				if ($cache) {
					$application->cacheItemPool()->saveDeferred($cache->expiresAfter($application->configuration->getPath(__METHOD__ . '::expiresAfter', 60))->set($command));
				}
			}
			$interface = null;
			$result = [];
			foreach ($command as $line) {
				if (preg_match('/^\S/', $line)) {
					[$interface, $flags] = StringTools::pair($line, ' ');
					$interface = rtrim($interface, ':');
					$result[$interface] = ['flags' => ltrim($flags), ];
				} else {
					$line = trim($line);
					$pairs = preg_split('/\s+/', $line);
					$type = rtrim(array_shift($pairs), ':');
					if ($pairs) {
						$id = StringTools::removePrefix(array_shift($pairs), 'addr:');
						$result[$interface][$type][$id] = ['value' => $id, $type => $id, ];
						while (count($pairs) > 1) {
							$name = rtrim(array_shift($pairs), ':');
							$value = array_shift($pairs);
							$result[$interface][$type][$id][$name] = $value;
						}
					}
				}
			}
			return $result;
		} catch (CommandFailed) {
			return [
				'localhost' => [
					'inet' => [
						'127.0.0.1' => [
							'value' => '127.0.0.1', 'inet' => '127.0.0.1', 'netmask' => '0xff000000',
						],
					], 'inet6' => [
						'::1' => [
							'value' => '::1', 'inet6' => '::1', 'prefixlen' => '128',
						], 'fe80::1%lo0' => [
							'value' => 'fe80::1%lo0', 'inet6' => 'fe80::1%lo0', 'prefixlen' => '64', 'scopeid' => '0x1',
						],
					],
				],
			];
		}
	}

	/**
	 * @return array
	 * @throws FilePermission
	 */
	private static function uptimeBinary(): array
	{
		if (!is_executable(self::BINARY_UPTIME)) {
			throw new FilePermission(self::BINARY_UPTIME, 'No access to load averages');
		}
		ob_start();
		system(self::BINARY_UPTIME);
		$uptime = trim(ob_get_clean());
		$pattern = ':';
		$pos = strrpos($uptime, $pattern);
		if ($pos === false) {
			throw new FilePermission(self::BINARY_UPTIME, 'Invalid response: {results}', [
				'results' => $uptime,
			]);
		}
		return explode(' ', str_replace(',', '', trim(substr($uptime, $pos + strlen($pattern)))));
	}

	/**
	 * Determine system current load averages using /proc/loadavg or system call to uptime
	 *
	 * @return array Uptime averages for the past 1 minute, 5 minutes, and 15 minutes
	 *         (typically)
	 * @throws FilePermission
	 */
	public static function loadAverages(): array
	{
		try {
			$uptime_string = explode(' ', File::contents('/proc/loadavg'));
		} catch (FileNotFound|FilePermission) {
			$uptime_string = self::uptimeBinary();
		}
		return [floatval($uptime_string[0]), floatval($uptime_string[1]), floatval($uptime_string[2]), ];
	}

	/**
	 * Retrieve volume information in a parsed manner from system call to "df"
	 *
	 * @param string $volume
	 *            Request for a specific volume (passed to df)
	 * @return array:array
	 */
	public static function volumeInfo(string $volume = ''): array
	{
		ob_start();
		$arg_volume = $volume ? escapeshellarg($volume) . ' ' : '';
		// Added -P to avoid issue on Mac OS X where Capacity and iused overlap
		$result = system("/bin/df -P -lk {$arg_volume}2> /dev/null");
		$volume_info = trim(ob_get_clean());
		if (!$result) {
			return [];
		}
		$volume_info = explode("\n", $volume_info);
		$volume_info = Text::parseColumns($volume_info);
		// FreeBSD:	Filesystem  1024-blocks     Used Avail 		Capacity 	Mounted on
		// Debian:	Filesystem  1K-blocks       Used Available	Use%		Mounted on
		// Linux:	Filesystem  1K-blocks       Used Available	Use%		Mounted on
		// Darwin:  Filesystem  1024-blocks     Used Available  Capacity iused ifree %iused Mounted on
		// Darwin:  Filesystem   1024-blocks       Used  Available Capacity  Mounted on
		$normalized_headers = [
			'1024-blocks' => 'total', '1k-blocks' => 'total', 'avail' => 'free', 'available' => 'free',
			'use%' => 'used_percent', 'capacity' => 'used_percent', 'mounted' => 'path', 'mounted on' => 'path',
			'filesystem' => 'filesystem',
		];
		$result = [];
		foreach ($volume_info as $volume_data) {
			$row = [];
			foreach ($volume_data as $field => $value) {
				$field = strtolower($field);
				$field = $normalized_headers[$field] ?? $field;
				$row[$field] = $value;
			}
			foreach (['total', 'used', 'free'] as $kilobyte_multiply) {
				if (array_key_exists($kilobyte_multiply, $row)) {
					$row[$kilobyte_multiply] *= 1024;
				}
			}
			$row['used_percent'] = intval(substr($row['used_percent'], 0, -1));
			$result[$row['path']] = $row;
		}
		return $result;
	}

	/**
	 * Tip files for distro
	 *
	 * @var array
	 * @see self::distro()
	 */
	private static array $distroTips = [
		'Novell SUSE' => '/etc/SUSE-release',
		'Red Hat' => [
			'/etc/redhat-release',
			'/etc/redhat_version',
		],
		'Fedora' => '/etc/fedora-release',
		'Slackware' => [
			'/etc/slackware-release',
			'/etc/slackware-version',
		],
		'Debian' => [
			'/etc/debian_release', '/etc/debian_version',
		],
		'Mandrake' => '/etc/mandrake-release',
		'Yellow dog' => '/etc/yellowdog-release',
		'Sun JDS' => '/etc/sun-release',
		'Solaris/Sparc' => '/etc/release',
		'Gentoo' => '/etc/gentoo-release',
		'UnitedLinux' => '/etc/UnitedLinux-release',
		'ubuntu' => '/etc/lsb-release',
	];

	/**
	 * Tips for system names
	 *
	 * @var array
	 * @see self::distro()
	 */
	private static array $systemNames = [
		'Darwin' => 'Mac OS X',
		'Linux' => 'Linux',
	];

	/**
	 * Based on http://www.novell.com/coolsolutions/feature/11251.html
	 *
	 * Determine the distribution we're running on.
	 *
	 * Returns an array with three keys:
	 *
	 * - brand - The brand for this system "Debian", "Fedora", etc.
	 * - distro - The system release name
	 * - release - The release version number
	 *
	 * @return array:array
	 * @see System_Test::test_distro()
	 */
	public static function distro(): array
	{
		$result = [
			'system' => $system = php_uname('s'),
			'release' => php_uname('r'),
			'arch' => php_uname('m'),
		];
		foreach (self::$distroTips as $distro => $files) {
			foreach (Types::toList($files) as $file) {
				if (file_exists($file)) {
					return $result + [
						'brand' => $distro,
						'distro' => trim(file_get_contents($file)),
						'source' => $file,
					];
				}
			}
		}
		return [
			'brand' => self::$systemNames[$system] ?? $system,
			'distro' => $system,
		];
	}

	/**
	 *
	 * @return int
	 */
	public static function memoryLimit(): int
	{
		$limit = ini_get('memory_limit');
		if (intval($limit) < 0) {
			return 0xFFFFFFFF;
		}
		return intval(Number::parse_bytes($limit));
	}
}
