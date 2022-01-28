<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage webapp
 * @author kent
 * @copyright &copy; 2022 Market Acumen, Inc.
 */
namespace zesk\WebApp;

use zesk\Application;
use zesk\Directory;
use zesk\Server;

/**
 *
 * @author kent
 *
 */
class Scanner {
	public function scan_for_instances(Application $application, $path) {
		$rules = [
			"rules_file" => [
				"#/webapp.json$#" => true,
				false,
			],
			"rules_directory_walk" => [
				"#/\.#" => false,
				"#/vendor/#" => false,
				"#/node_modules/#" => false,
				true,
			],
			"rules_directory" => false,
		];
		$files = Directory::list_recursive($path, $rules);
		$server = Server::singleton($application);
		$instances = [];
		foreach ($files as $webapp_json_file) {
			$instances[] = Instance::register_from_path($application, $server, $webapp_json_file);
		}
		return $instances;
	}
}
