<?php declare(strict_types=1);
/**
 *
 */
namespace zesk\ObjectCache;

use zesk\File as zeskFile;
use zesk\JSON;
use zesk\Directory;
use zesk\ORM;
use zesk\Exception_Directory_Create;

/**
 *
 * @author kent
 *
 */
class File extends Base {
	public $path = null;

	public function __construct($path = null) {
		$this->path = $path;
		$this->configured();
	}

	private function cachePath($add = null, $create = false) {
		$path = $this->path;
		if (!$path) {
			return null;
		}
		if ($add) {
			$path = path($path, $add);
		}
		if ($create && !Directory::create($path, 0o770)) {
			throw new Exception_Directory_Create($path);
		}
		return $path;
	}

	private function object_path(ORM $object, $create = false) {
		$id = $object->id();
		if (!$id) {
			return null;
		}
		if (is_array($id)) {
			ksort($id);
			$id = zeskFile::name_clean(JSON::encode($id));
		}
		return $this->cachePath(strtolower(get_class($object)) . "-$id", $create);
	}

	public function configured(): void {
		$path = $this->cachePath(null, true);
	}

	public function load(ORM $object, $key) {
		$path = $this->object_path($object);
		if (!$path) {
			return null;
		}
		$hash = self::hash_from_key($key);
		$cache_file = path($path, $hash . '.cache');
		if (file_exists($cache_file)) {
			return unserialize(file_get_contents($cache_file));
		}
		return null;
	}

	public function save(ORM $object, $key, $data) {
		$path = $this->object_path($object, ($data !== null));
		if (!$path) {
			return null;
		}
		$hash = self::hash_from_key($key);
		$cache_file = path($path, $hash . '.cache');
		if ($data === null) {
			@unlink($cache_file);
		} else {
			file_put_contents($cache_file, serialize($data));
		}
	}

	public function invalidate(ORM $object, $key = null): void {
		$path = $this->object_path($object);
		if ($key !== null) {
			zeskFile::unlink(path($path, self::hash_from_key($key) . '.cache'));
		} elseif (is_dir($path)) {
			Directory::delete($path);
		}
	}

	/**
	 * Convert key to string
	 *
	 * @param mixed $key
	 * @return string
	 */
	private static function hash_from_key($key = null) {
		if (is_array($key)) {
			ksort($key);
			$key = serialize($key);
		}
		return md5($key);
	}
}
