<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage webapp
 * @author kent
 * @copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk;

/**
 * @author kent
 */
namespace zesk\WebApp;

use zesk\JSON;
use zesk\File;

class Type_Node extends Type {
	protected $priority = 1000;

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
		return path($this->path, 'package.json');
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\WebApp\Type::version()
	 */
	public function version() {
		try {
			$json = JSON::decode(File::contents($this->package_json_path(), '{}'));
			return $json['version'] ?? null;
		} catch (\Exception $e) {
			$this->exception = $e;
			return null;
		}
	}
}
