<?php

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
		$this->cache = $this->$factory();
		self::configure_object_cache($this->cache);
	}
	public static function _factory_file() {
		return new Object_Cache_File(zesk::cache_path('object_cache'));
	}
	public static function _factory_database() {
		return new Object_Cache_Database();
	}

	private static function configure_object_cache(Object_Cache $cache) {
		$invalidate = array(
			$cache,
			"invalidate"
		);
		zesk::add_hook("Object::cache-load", array(
			$cache,
			"load"
		));
		zesk::add_hook("Object::cache-save", array(
			$cache,
			"save"
		));
		zesk::add_hook("Object::cache-dirty", $invalidate);
		zesk::add_hook("Object::insert", $invalidate);
		zesk::add_hook("Object::delete", $invalidate);
		zesk::add_hook("Object::update", $invalidate);
	}
}

