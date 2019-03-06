<?php
/**
 * @package zesk
 * @subpackage webapp
 * @author kent
 * @copyright &copy; 2019 Market Acumen, Inc.
 */
namespace zesk\WebApp;

/**
 * @see Class_Site
 * @author kent
 * @property integer $id
 * @property Instance $instance
 * @property string $name
 * @property string $code
 * @property string $type
 * @property integer $priority
 * @property string $path
 * @property array $data
 * @property array $errors
 * @property boolean $valid
 */
class Site extends ORM {
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
			->where("instance", $this->instance)
			->what("*max", "MAX(priority)")
			->one_integer("max", -1);
	}

	public function domains() {
		$cluster = Cluster::find_from_site($this);
		$clusters = $cluster ? $this->application->orm_registry(Domain::class)
			->query_select()
			->where(array(
			'type' => Cluster::class,
			'target' => $cluster->id(),
		))
			->orm_iterator()
			->to_array() : array();
		$sites = $this->application->orm_registry(Domain::class)
			->query_select()
			->where(array(
			'type' => self::class,
			'target' => $this->id(),
		))
			->orm_iterator()
			->to_array();

		return array_merge($clusters, $sites);
	}

	/**
	 * Make sure it's a valid structure
	 */
	public function validate_structure() {
		$errors = array();
		$code = $this->code;
		if (empty($code)) {
			$errors['code'] = "code is required";
		} elseif (preg_match("/[^-_a-zA-z0-9 ]/", $code)) {
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
			$path = path($this->instance->path, $path);
			if (!is_dir($path)) {
				$errors['path'] = "path must be a directory: $path";
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
