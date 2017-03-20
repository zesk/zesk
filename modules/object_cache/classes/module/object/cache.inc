<?php
class Module_Object_Cache extends zesk\Module {
	
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
		self::configure_object_cache(zesk()->hooks, $this->cache);
	}
	public static function _factory_file(zesk\Application $application) {
		return new Object_Cache_File($application->cache_path('object_cache'));
	}
	public static function _factory_database(zesk\Application $application) {
		return new Object_Cache_Database();
	}
	private static function configure_object_cache(zesk\Hooks $hook, Object_Cache $cache) {
		$invalidate = array(
			$cache,
			"invalidate"
		);
		$hook->add("zesk\Object::cache-load", array(
			$cache,
			"load"
		));
		$hook->add("zesk\Object::cache-save", array(
			$cache,
			"save"
		));
		$hook->add("zesk\Object::cache-dirty", $invalidate);
		$hook->add("zesk\Object::insert", $invalidate);
		$hook->add("zesk\Object::delete", $invalidate);
		$hook->add("zesk\Object::update", $invalidate);
	}
}

