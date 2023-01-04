<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage webapp
 * @author kent
 * @copyright &copy; 2022, Market Acumen, Inc.
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
		$instance = $application->ormFactory(self::class, [
			'path' => $path,
			'server' => $server,
		]);
		return $instance->find();
	}

	/**
	 * Retrieve path to webapp.json for this instance. Does not verify this is on the same server
	 * as the requestor!
	 *
	 * @return string
	 */
	public function json_path() {
		return path($this->path, 'webapp.json');
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
		$instance = $application->ormFactory(self::class);
		return $instance->find([
			'code' => $code,
			'server' => $server,
		]);
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
			throw new Exception_Semantics('{class} JSON at {webapp_path} has no code set', [
				'class' => __CLASS__,
				'webapp_path' => $webapp_path,
			]);
		}
		/**
		 * @var self $instance
		 */
		$instance = $application->ormFactory(self::class, [
			'path' => $path,
			'server' => $server,
			'hash' => $hash,
		] + ArrayTools::filter($json, [
			'name',
			'code',
		]))->register();
		$instance->json = $json;
		$instance->hash = $hash;
		$instance->code = $code;

		$mtime = Timestamp::factory(filemtime($webapp_path), 'UTC');
		$updated = $instance->updated;
		$changes = $instance->changes();
		if (count($changes) !== 0 || !$updated instanceof Timestamp || $updated->before($mtime)) {
			$instance->store();
			$instance->callHook('before_sites_changed');
			$valid_sites = [];

			foreach (to_list($json['sites'] ?? null) as $site_members) {
				$site = $instance->register_site($generator, $site_members);
				$valid_sites[] = $site->id();
			}
			$where = [
				'instance' => $instance,
			];
			if (count($valid_sites) > 0) {
				$where['id|!=|AND'] = $valid_sites;
			}
			$delete = $application->ormFactory(Site::class)->queryDelete()->where($where);
			// $application->logger->debug(strval($delete));
			$delete->execute();
			$instance->callHook('after_sites_changed');
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
		$site = $this->application->ormFactory(Site::class, [
			'instance' => $this,
		]);
		$site_member_names = $site->memberNames();
		$data = ArrayTools::keysRemove($members, $site_member_names);
		$members = ArrayTools::filter($members, $site_member_names);
		if (!isset($members['code'])) {
			throw new Exception_Semantics('{class} Site {code} {name} missing code', [
				'class' => Site::class,
			] + $members);
		}
		if (!isset($members['path'])) {
			throw new Exception_Semantics('{class} Site {code} {name} missing path', [
				'class' => Site::class,
			] + $members);
		}
		if (!isset($members['type'])) {
			$members['type'] = 'standard';
		}
		$site = $site->setMember($members)
			->register()
			->setMember($members)
			->store();
		$site->data = $data;
		$errors = $site->validate_structure();
		$errors = array_merge($errors, toArray($generator->validate($members + $data)));
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
		$priorities = [];
		foreach ($types as $index => $type) {
			if ($type->valid()) {
				$priorities[] = [
					'type' => $type,
					'weight' => $type->priority(),
				];
			}
		}
		usort($priorities, 'zesk_sort_weight_array');
		$priorities = ArrayTools::collapse($priorities, 'type');
		foreach ($priorities as $type) {
			/* @var $type Type */
			$version = $type->version();
			if (!empty($version)) {
				$this->appversion = $version;
				$this->apptype = strtolower(StringTools::removePrefix(PHP::parseClass($type::class), 'Type_'));
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
		$members = $json['repository'] + ArrayTools::filter($json, [
			'name',
			'code',
		]);
		$this->repository = $this->application->ormFactory(Repository::class, $members)
			->register()
			->setMember($members)
			->store();
		;
		$this->store();
	}

	/**
	 *
	 */
	public function remove_dead_instances(): void {
		$query = $this->querySelect('X')->link(Server::class, [
			'alias' => 'S',
			'require' => false,
		])->addWhere('s.id', null);
		$iterator = $query->ormIterator();
		foreach ($iterator as $instance) {
			/* @var $instance self */
			$sid = $instance->memberInteger('server');
			$this->application->logger->notice('Deleting instance #{id} {path} associated with dead server #{sid}', $instance->members([
				'id',
				'path',
			]) + [
				'sid' => $sid,
			]);
			$instance->delete();
		}
	}
}
