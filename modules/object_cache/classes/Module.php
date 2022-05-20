<?php declare(strict_types=1);
namespace zesk\ObjectCache;

use zesk\ORM;
use zesk\Application;

class Module extends \zesk\Module {
	/*
	 * @var Base
	 */
	public $cache = null;

	public function hook_configured(): void {
		if ($this->cache instanceof Base) {
			return;
		}
		$type = $this->option('type', 'file');
		$factory = "_factory_$type";
		if (!method_exists($this, $factory)) {
			$factory = '_factory_file';
		}
		$this->cache = $this->$factory($this->application);
		$this->configure_object_cache($this->cache);
	}

	public static function _factory_file(Application $application) {
		return new File($application->cache_path('object_cache'));
	}

	public static function _factory_database(Application $application) {
		return new Database();
	}

	private function configure_object_cache(Base $cache): void {
		$hooks = $this->application->hooks;
		$invalidate = [
			$cache,
			'invalidate',
		];
		$hooks->add("zesk\ORM::cache-load", [
			$cache,
			'load',
		]);
		$hooks->add("zesk\ORM::cache-save", [
			$cache,
			'save',
		]);
		$hooks->add("zesk\ORM::cache-dirty", $invalidate);
		$hooks->add("zesk\ORM::insert", $invalidate);
		$hooks->add("zesk\ORM::delete", $invalidate);
		$hooks->add("zesk\ORM::update", $invalidate);
	}
}
