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
use zesk\Exception_Semantics;
use zesk\Timestamp;
use zesk\Exception_Directory_NotFound;
use zesk\PHP;
use zesk\StringTools;

/**
 * @see Class_Instance
 *
 * @author kent
 * @property integer $id
 * @property Server $server
 * @property string $path
 * @property string $code
 * @property string $name
 * @property string $appversion
 * @property string $hash
 * @property string $json
 * @property Timestamp $updated
 * @property Timestamp $serving
 */
class Instance extends ORM {
	/**
	 *
	 * @param Application $application
	 * @param Server $server
	 * @param string $webapp_path
	 * @return self
	 */
	public static function find_from_path(Application $application, Server $server, $webapp_path) {
		$path = dirname($webapp_path);
		$instance = $application->orm_factory(self::class, array(
			"path" => $path,
			"server" => $server,
		));
		return $instance->find();
	}

	/**
	 * Retrieve path to webapp.json for this instance. Does not verify this is on the same server
	 * as the requestor!
	 *
	 * @return string
	 */
	public function json_path() {
		return path($this->path, "webapp.json");
	}

	public function load_json() {
		return JSON::decode(File::contents($this->json_path()));
	}

	/**
	 *
	 * @param Application $application
	 * @param Server $server
	 * @param string $code
	 * @return self
	 */
	public static function find_from_code(Application $application, Server $server, $code) {
		$instance = $application->orm_factory(self::class);
		return $instance->find(array(
			"code" => $code,
			"server" => $server,
		));
	}

	/**
	 *
	 * @param Application $application
	 * @param Server $server
	 * @param string $webapp_path
	 * @return self
	 */
	public static function register_from_path(Application $application, Server $server, Generator $generator, $webapp_path) {
		$contents = File::contents($webapp_path);
		$json = JSON::decode($contents);
		$path = dirname($webapp_path);
		$hash = md5($contents);
		$code = $json['code'];

		if (!$code) {
			throw new Exception_Semantics("{class} JSON at {webapp_path} has no code set", array(
				"class" => __CLASS__,
				"webapp_path" => $webapp_path,
			));
		}
		/**
		 * @var self $instance
		 */
		$instance = $application->orm_factory(self::class, array(
			"path" => $path,
			"server" => $server,
			"hash" => $hash,
		) + ArrayTools::filter($json, array(
			"name",
			"code",
		)))->register();
		$instance->json = $json;
		$instance->hash = $hash;
		$instance->code = $code;

		$mtime = Timestamp::factory(filemtime($webapp_path), "UTC");
		$updated = $instance->updated;
		$changes = $instance->changes();
		if (count($changes) !== 0 || !$updated instanceof Timestamp || $updated->before($mtime)) {
			$instance->store();
			$instance->call_hook("before_sites_changed");
			$valid_sites = array();

			foreach (to_list(avalue($json, 'sites')) as $site_members) {
				$site = $instance->register_site($generator, $site_members);
				$valid_sites[] = $site->id();
			}
			$where = array(
				"instance" => $instance,
			);
			if (count($valid_sites) > 0) {
				$where['id|!=|AND'] = $valid_sites;
			}
			$delete = $application->orm_factory(Site::class)->query_delete()->where($where);
			// $application->logger->debug(strval($delete));
			$delete->execute();
			$instance->call_hook("after_sites_changed");
		}

		return $instance;
	}

	/**
	 *
	 * @param array $members
	 * @return Host
	 */
	public function register_site(Generator $generator, array $members) {
		/* @var $site Site */
		ksort($members);
		$site = $this->application->orm_factory(Site::class, array(
			"instance" => $this,
		));
		$site_member_names = $site->member_names();
		$data = ArrayTools::remove($members, $site_member_names);
		$members = ArrayTools::filter($members, $site_member_names);
		if (!isset($members['code'])) {
			throw new Exception_Semantics("{class} Site {code} {name} missing code", array(
				"class" => Site::class,
			) + $members);
		}
		if (!isset($members['path'])) {
			throw new Exception_Semantics("{class} Site {code} {name} missing path", array(
				"class" => Site::class,
			) + $members);
		}
		if (!isset($members['type'])) {
			$members['type'] = 'standard';
		}
		$site = $site->set_member($members)
			->register()
			->set_member($members)
			->store();
		$site->data = $data;
		$errors = $site->validate_structure();
		$errors = array_merge($errors, to_array($generator->validate($members + $data)));
		$site->errors = $errors;
		$site->valid = count($errors) === 0;
		return $site->store();
	}

	/**
	 *
	 * @throws Exception_Directory_NotFound
	 */
	public function refresh_appversion() {
		/**
		 * @var Module $webapp
		 */
		$webapp = $this->application->webapp_module();
		$root = $webapp->app_root_path();
		$path = $this->path;
		if (!is_dir($path)) {
			throw new Exception_Directory_NotFound($path);
		}
		$types = Type::factory_all_types($this->application, $path);
		$priorities = array();
		foreach ($types as $index => $type) {
			if ($type->valid()) {
				$priorities[] = array(
					'type' => $type,
					'weight' => $type->priority(),
				);
			}
		}
		usort($priorities, "zesk_sort_weight_array");
		$priorities = ArrayTools::collapse($priorities, "type");
		foreach ($priorities as $type) {
			/* @var $type Type */
			$version = $type->version();
			if (!empty($version)) {
				$this->appversion = $version;
				$this->apptype = strtolower(StringTools::unprefix(PHP::parse_class(get_class($type)), "Type_"));
				return true;
			}
		}
		return false;
	}

	/**
	 *
	 */
	public function refresh_repository() {
		$json = $this->load_json();
		if (!isset($json['repository'])) {
			return null;
		}
		$members = $json['repository'] + ArrayTools::filter($json, array(
			"name",
			"code",
		));
		$this->repository = $this->application->orm_factory(Repository::class, $members)
			->register()
			->set_member($members)
			->store();
		;
		$this->store();
	}

	/**
	 *
	 */
	public function remove_dead_instances() {
		$query = $this->query_select("X")->link(Server::class, array(
			'alias' => 'S',
			'require' => false,
		))->where('s.id', null);
		$iterator = $query->orm_iterator();
		foreach ($iterator as $instance) {
			/* @var $instance self */
			$sid = $instance->member_integer("server");
			$this->application->logger->notice("Deleting instance #{id} {path} associated with dead server #{sid}", $instance->members(array(
				"id",
				"path",
			)) + array(
				"sid" => $sid,
			));
			$instance->delete();
		}
	}
}
