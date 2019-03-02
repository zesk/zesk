<?php
/**
 * @package zesk
 * @subpackage webapp
 * @author kent
 * @copyright &copy; 2019 Market Acumen, Inc.
 */
namespace zesk\WebApp;

use zesk\File;
use zesk\JSON;
use zesk\Application;
use zesk\ArrayTools;
use zesk\Server;

/**
 * @see Class_Instance
 *
 * @author kent
 *
 */
class Instance extends ORM {
	/**
	 *
	 * @param Application $application
	 * @param Server $server
	 * @param string $webapp_path
	 * @return self
	 */
	public static function register_from_path(Application $application, Server $server, Generator $generator, $webapp_path) {
		$path = dirname($webapp_path);
		$contents = File::contents($path);
		$json = JSON::decode($contents);

		/**
		 * @var self $instance
		 */
		$instance = $application->orm_factory(self::class, array(
			"path" => $path,
			"server" => $server,
			"hash" => $hash = md5($contents),
		) + ArrayTools::filter($json, array(
			"name",
			"code",
		)))->register();
		$instance->hash = $hash;
		$instance->code = $json['code'];

		$changes = $instance->changes();
		if (count($changes) !== 0) {
			$instance->store();
			$instance->call_hook("before_host_change");
			$valid_hosts = array();
			foreach (to_list(avalue($json, 'applications')) as $host_members) {
				$host = $instance->register_host($generator, $host_members);
				$valid_hosts[] = $host->id();
			}
			$where = array(
				"server" => $server,
			);
			if (count($valid_hosts) > 0) {
				$where['id|!='] = $valid_hosts;
			}
			$application->orm_factory(Host::class)
				->query_delete()
				->where($where)
				->execute();
			;
			$instance->call_hook("after_host_change");
		}

		return $instance;
	}

	/**
	 *
	 * @param array $host_members
	 * @return Host
	 */
	public function register_host(Generator $generator, array $host_members) {
		/* @var $host Host */
		ksort($host_members);
		$host = $this->application->orm_factory(Host::class, array(
			"instance" => $this,
		));
		$host = $host->set_member(ArrayTools::filter($host_members, $host->member_names()))->register();
		$data = ArrayTools::remove($host_members, $host->member_names());
		$host->data = $data;
		$host->errors = $errors = $host->validate_structure($generator);
		$host->valid = count($errors) === 0;
		$host->store();
		return $host;
	}
}
