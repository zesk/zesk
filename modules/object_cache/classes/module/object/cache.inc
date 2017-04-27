<?php
namespace zesk;

class Module_Object_Cache extends Module {
	
	/*
	 * @var Object_Cache
	 */
	public $cache = null;
	public function initialize() {
	}
	public function hook_configured() {
		if ($this->cache instanceof Object_Cache) {
			return;
		}
		$type = $this->option('type', 'file');
		$factory = "_factory_$type";
		if (!method_exists($this, $factory)) {
			$factory = "_factory_file";
		}
		$this->cache = $this->$factory($this->application);
		$this->configure_object_cache($this->cache);
	}
	public static function _factory_file(Application $application) {
		return new Object_Cache_File($application->cache_path('object_cache'));
	}
	public static function _factory_database(Application $application) {
		return new Object_Cache_Database();
	}
	private static function configure_object_cache(Object_Cache $cache) {
		$hooks = $this->application->hooks;
		$invalidate = array(
			$cache,
			"invalidate"
		);
		$hooks->add("zesk\Object::cache-load", array(
			$cache,
			"load"
		));
		$hooks->add("zesk\Object::cache-save", array(
			$cache,
			"save"
		));
		$hooks->add("zesk\Object::cache-dirty", $invalidate);
		$hooks->add("zesk\Object::insert", $invalidate);
		$hooks->add("zesk\Object::delete", $invalidate);
		$hooks->add("zesk\Object::update", $invalidate);
	}
}

