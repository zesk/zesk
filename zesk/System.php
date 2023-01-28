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

/**
 *
 * @author kent
 *
 */
class System {
	private static $arr;

	/**
	 *
	 * @param Application $application
	 */
	public static function hooks(Application $app): void {
		$app->hooks->add(Hooks::HOOK_CONFIGURED, [__CLASS__, 'configured', ]);
	}

	public static function configured(Application $application): void {
		self::host_id($application->configuration->get('HOST', $_ENV[ 'HOST'] ??  $_SERVER['HOST'] ?? 'localhost'));
	}

	/**
	 *
	 * @var string
	 */
	private static $host_id = null;

	/**
	 * Set/get the name of the host machine for use elsewhere
	 *
	 * @param string $set Set a value for the host ID
	 * @return string
	 */
	public static function host_id($set = null) {
		if ($set !== null) {
			self::$host_id = $set;
		}
		return self::$host_id;
	}

	/**
	 *
	 * @return string
	 */
	public static function uname(): string {
		return php_uname('n');
	}

	/**
	 * Get current process ID
	 *
	 * @return integer
	 */
	public static function process_id() {
		return getmypid();
	}

	/**
	 * Load IP addresses for this sytem
	 *
	 * @return array of interface => $ip
	 */
	public static function ip_addresses(Application $application): array {
		$interfaces = self::iffilter(self::ifconfig($application), ['inet', 'ether']);
		$ips = [];
		foreach ($interfaces as $interface => $values) {
			if (array_key_exists('inet', $values)) {
				$ip = first(array_keys($values['inet']));
				if (IPv4::valid($ip)) {
					$ips[$interface] = $ip;
				}
			}
		}
		return $ips;
	}

	/**
	 * Load MAC addresses for this sytem
	 *
	 * @return array of interface => $ip
	 */
	public static function mac_addresses(Application $application): array {
		$interfaces = self::ifconfig($application, 'inet;ether');
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
	 * Filter ifconfig results to find interfaces with specific subkey(s)
	 *
	 * @param array $results
	 * @param array|string|null $filter
	 * @return array
	 */
	public static function iffilter(array $results, array|string $filter = null): array {
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
	 * @return array
	 */
	public static function ifconfig(Application $application) {
		$result = [];

		try {
			$cache = $application->cacheItemPool()->getItem(__METHOD__);
			if ($cache->isHit()) {
				$command = $cache->get();
			} else {
				$command = $application->process->execute('ifconfig');
				$application->cacheItemPool()->saveDeferred($cache->expiresAfter($application->configuration->getPath(__METHOD__ . '::expires_after', 60))->set($command));
			}
			$interface = null;
			foreach ($command as $line) {
				if (preg_match('/^\S/', $line)) {
					[$interface, $flags] = pair($line, ' ');
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
		} catch (Exception) {
			$result = [
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
		return $result;
	}

	/**
	 * Determine system current load averages using /proc/loadavg or system call to uptime
	 *
	 * @return array:float Uptime averages for the past 1 minute, 5 minutes, and 15 minutes
	 *         (typically)
	 */
	public static function load_averages() {
		$uptime_string = null;
		if (file_exists('/proc/loadavg')) {
			$uptime_string = explode(' ', File::contents('/proc/loadavg', ''));
		} else {
			ob_start();
			system('/usr/bin/uptime');
			$uptime = trim(ob_get_clean());
			$pattern = ':';
			$pos = strrpos($uptime, $pattern);
			if ($pos !== false) {
				$uptime_string = explode(' ', str_replace(',', '', trim(substr($uptime, $pos + strlen($pattern)))));
			}
		}
		$loads = [floatval($uptime_string[0]), floatval($uptime_string[1]), floatval($uptime_string[2]), ];
		return $loads;
	}

	/**
	 * Retrieve volume information in a parsed manner from system call to "df"
	 *
	 * @param string $volume
	 *            Request for a specific volume (passed to df)
	 * @return array:array
	 */
	public static function volume_info(string $volume = ''): array {
		ob_start();
		$max_tokens = 10;
		$arg_volume = $volume ? escapeshellarg($volume) . ' ' : '';
		// Added -P to avoid issue on Mac OS X where Capacity and iused overlap
		$result = system("/bin/df -P -lk {$arg_volume}2> /dev/null");
		$volume_info = trim(ob_get_clean());
		if (!$result) {
			return [];
		}
		$volume_info = explode("\n", $volume_info);
		$volume_info = Text::parse_columns($volume_info);
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
	 */
	public static function distro(): array {
		static $distro_tips = [
			'Novell SUSE' => '/etc/SUSE-release', 'Red Hat' => ['/etc/redhat-release', '/etc/redhat_version', ],
			'Fedora' => '/etc/fedora-release', 'Slackware' => ['/etc/slackware-release', '/etc/slackware-version', ],
			'Debian' => ['/etc/debian_release', '/etc/debian_version', ], 'Mandrake' => '/etc/mandrake-release',
			'Yellow dog' => '/etc/yellowdog-release', 'Sun JDS' => '/etc/sun-release',
			'Solaris/Sparc' => '/etc/release', 'Gentoo' => '/etc/gentoo-release',
			'UnitedLinux' => '/etc/UnitedLinux-release', 'ubuntu' => '/etc/lsb-release',
		];
		static $sysnames = ['Darwin' => 'Mac OS X', ];
		$relname = php_uname('r');
		foreach ($distro_tips as $distro => $files) {
			foreach (toList($files) as $file) {
				if (file_exists($file)) {
					return ['brand' => $distro, 'distro' => trim(file_get_contents($file)), 'release' => $relname, ];
				}
			}
		}
		$sysname = php_uname('s');
		self::$arr = ['brand' => $sysnames[$sysname] ?? $sysname, 'distro' => $sysname, 'release' => $relname, ];
		return self::$arr;
	}

	/**
	 *
	 * @return number
	 */
	public static function memory_limit(): int {
		$limit = ini_get('memory_limit');
		if (intval($limit) < 0) {
			return 0xFFFFFFFF;
		}
		return Number::parse_bytes($limit);
	}
}
