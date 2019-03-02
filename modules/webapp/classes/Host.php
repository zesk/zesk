<?php
/**
 * @package zesk
 * @subpackage webapp
 * @author kent
 * @copyright &copy; 2019 Market Acumen, Inc.
 */
namespace zesk\WebApp;

use zesk\Application;

class Host extends ORM {
	/**
	 *
	 * @var string
	 */
	const HOST_TYPE_DIRECTORY_INDEX = "directory-index";

	/**
	 *
	 * @var string
	 */
	const HOST_TYPE_DEFAULT = "default";

	/**
	 *
	 * @var array
	 */
	private static $types = array(
		self::HOST_TYPE_DIRECTORY_INDEX => "Directory Index",
		self::HOST_TYPE_DEFAULT => "default",
	);

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\ORM::pre_insert()
	 */
	public function hook_pre_insert(array $members) {
		$members['priority'] = $this->last_priority() + 1;
		return $members;
	}

	/**
	 *
	 * @return integer
	 */
	protected function last_priority() {
		return $this->query_select()
			->where("server", $this->server)
			->what("max", "MAX(priority)")
			->one_integer("max");
	}

	/**
	 * Make sure it's a valid structure
	 */
	public function validate_structure() {
		$errors = array();
		$code = $this->code;
		if (empty($code)) {
			$errors['code'] = "code is required";
		} elseif (preg_match("/[-_a-zA-z0-9 ]/", $code)) {
			$errors['code'] = "code has incorrect values";
		}
		$name = trim($this->name);
		if (empty($name)) {
			$errors['name'] = "name is required";
		}
		$path = $this->path;
		if (empty($path)) {
			$errors['path'] = "path is required";
		} else {
			$path = $this->application->paths->expand($path);
			if (!is_dir($path)) {
				$errors['path'] = "path must be a directory";
			}
		}
		$type = $this->type;
		if (!empty($type)) {
			if (array_key_exists($type, self::$types)) {
				$errors['type'] = "invalid type, must be one of: " . implode(",", array_keys(self::$types));
			}
		}
		if (!$this->instance instanceof Instance) {
			$errors['instance'] = "Should supply an instance source";
		}
		return $errors;
	}
}
