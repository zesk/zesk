<?php
/**
 * @package zesk
 * @subpackage DaemonTools
 * @author kent
 * @copyright &copy; 2018 Market Acumen, Inc.
 */
namespace zesk;

/**
 * @author kent
 */
namespace zesk\DaemonTools;

use zesk\Exception_Syntax;
use zesk\Options;

/**
 * @property $duration integer
 * @property $pid integer
 * @property $ok boolean
 * @author kent
 *
 */
class Service extends Options {

	/**
	 *
	 * @var Module
	 */
	private $module = null;
	/**
	 *
	 * @var string
	 */
	public $path = null;
	/**
	 *
	 * @var string
	 */
	public $name = null;
	/**
	 *
	 * @param string $name
	 * @param array $options
	 */
	public function __construct(Module $module, $name, array $options = array()) {
		$this->module = $module;
		parent::__construct($options);
		$this->path = $name;
		$this->name = basename($name);
	}
	/**
	 *
	 * @param string $name
	 * @param array $options
	 * @return self
	 */
	public static function factory(Module $module, $name, array $options = array()) {
		return new self($module, $name, $options);
	}

	/**
	 *
	 * @param Module $module
	 * @param string $line
	 * @return \zesk\DaemonTools\Service
	 */
	public static function from_svstat(Module $module, $line) {
		$options = self::svstat_to_options();
		return self::factory($module, $options['name'], $options);
	}

	/**
	 *
	 * @param string $line
	 * @throws Exception_Syntax
	 * @return array
	 */
	private static function svstat_to_options($line) {
		list($name, $status) = pair($line, ":", $line, null);
		if ($status !== null) {
			// /etc/service/servicename: down 0 seconds, normally up
			// /etc/service/servicename: up (pid 17398) 1 seconds
			// /etc/service/servicename: up (pid 13002) 78364 seconds, want down
			// /etc/service/monitor-services: supervise not running
			//
			$status = trim($status);
			$result = array(
				"path" => $name
			);
			if (preg_match('#^up \\(pid ([0-9]+)\\) ([0-9]+) seconds#', $status, $matches)) {
				return $result + array(
					"status" => "up",
					"ok" => true,
					"pid" => intval($matches[1]),
					"duration" => intval($matches[2])
				);
			}
			if (preg_match('#^down ([0-9]+) seconds#', $status, $matches)) {
				return $result + array(
					"status" => "down",
					"ok" => true,
					"duration" => intval($matches[1])
				);
			}
			if (preg_match('#^supervise not running$#', $status, $matches)) {
				return $result + array(
					"status" => "down",
					"ok" => false
				);
			}
		}
		throw new Exception_Syntax("Does not appear to be a svstat output line: \"{line}\"", array(
			"line" => $line
		));
	}

	/**
	 *
	 * @return string
	 */
	function __toString() {
		$pattern = !$this->ok ? "{path}: supervise not running" : avalue(array(
			"up" => "{path}: {status} (pid {pid}) {duration} seconds",
			"down" => "{path}: {status} {duration} seconds, normally up"
		), $this->status, "{path}: {status}");
		return map($pattern, $this->options);
	}
}