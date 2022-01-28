<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage webapp
 * @author kent
 * @copyright &copy; 2022 Market Acumen, Inc.
 */
namespace zesk;

/**
 * @author kent
 */
namespace zesk\WebApp;

use zesk\JSON;
use zesk\File;

class Type_Custom extends Type {
	protected $priority = 10000;

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\WebApp\Type::valid()
	 */
	public function valid() {
		return file_exists($this->package_json_path());
	}

	/**
	 *
	 * @return string
	 */
	public function package_json_path() {
		return path($this->path, "webapp.json");
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\WebApp\Type::version()
	 */
	public function version() {
		try {
			$json = JSON::decode(File::contents($this->package_json_path(), '{}'));
			$version = avalue($json, "version", null);
			if ($version) {
				return $version;
			}
			if (array_key_exists("version_file", $json)) {
				$version = File::contents(path($this->path, $json['version_file']));
				return $version;
			}
		} catch (\Exception $e) {
			$this->exception = $e;
			return null;
		}
	}
}
