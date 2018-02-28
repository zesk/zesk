<?php
namespace zesk;

use Psr\Cache\CacheItemInterface;

class ORM_CacheItem implements CacheItemInterface {
	/**
	 *
	 * @var Application
	 */
	private $application;
	/**
	 *
	 * @var CacheItemInterface
	 */
	private $item;

	/**
	 *
	 * @var boolean
	 */
	private $is_hit = null;

	/**
	 *
	 * @var array
	 */
	private $depends = array();

	/**
	 *
	 * @var array
	 */
	private $class_depends = array();

	/**
	 *
	 */
	public function __wakeup() {
		$this->application = __wakeup_application();
	}
	/**
	 *
	 * @return string[]
	 */
	public function __sleep() {
		return array(
			"item",
			"depends",
			"class_depends"
		);
	}
	/**
	 *
	 * @param CacheItemInterface $actual
	 */
	public function __construct(Application $application, CacheItemInterface $actual) {
		$this->application = $application;
		$this->item = $actual;
		$this->is_hit = null;
	}
	/**
	 * Returns the key for the current cache item.
	 *
	 * The key is loaded by the Implementing Library, but should be available to
	 * the higher level callers when needed.
	 *
	 * @return string The key string for this cache item.
	 */
	public function getKey() {
		return $this->item->getKey();
	}

	/**
	 * Retrieves the value of the item from the cache associated with this object's key.
	 *
	 * The value returned must be identical to the value originally stored by set().
	 *
	 * If isHit() returns false, this method MUST return null. Note that null
	 * is a legitimate cached value, so the isHit() method SHOULD be used to
	 * differentiate between "null value was found" and "no value was found."
	 *
	 * @return mixed The value corresponding to this cache item's key, or null if not found.
	 */
	public function get() {
		if (!$this->isHit()) {
			return null;
		}
		$result = $this->item->get();
		if (is_array($result)) {
			return $result['object'];
		}
		return null;
	}

	/**
	 * Confirms if the cache item lookup resulted in a cache hit.
	 *
	 * Note: This method MUST NOT have a race condition between calling isHit()
	 * and calling get().
	 *
	 * @return bool True if the request resulted in a cache hit. False otherwise.
	 */
	public function isHit() {
		// Cache and return value
		if ($this->is_hit !== null) {
			return $this->is_hit;
		}
		return $this->is_hit = $this->_isHit();
	}

	/**
	 *
	 * @return boolean|unknown
	 */
	private function _isHit() {
		if (!$this->item->isHit()) {
			return false;
		}
		$value = $this->item->get();
		if (!is_array($value)) {
			return false;
		}
		if (!$this->depends_valid($value['depends'])) {
			return false;
		}
		if (!$this->class_depends_valid($value['class_depends'])) {
			return false;
		}
		return true;
	}
	private function depends_valid($value) {
		foreach ($this->depends as $class => $target) {
		}
	}
	/**
	 * Sets the value represented by this cache item.
	 *
	 * The $value argument may be any item that can be serialized by PHP,
	 * although the method of serialization is left up to the Implementing
	 * Library.
	 *
	 * @param mixed $value
	 *        	The serializable value to be stored.
	 *
	 * @return static The invoked object.
	 */
	public function set($value) {
		$value = $this->get();
		if (!is_array($value)) {
			$value = array(
				"depends" => array(),
				"class_depends" => array()
			);
		}
		if (count($this->depends) > 0) {
			$value['depends'] = $this->compute_depends();
		}
		if (count($this->class_depends) > 0) {
			$value['class_depends'] = $this->compute_class_depends();
		}
		$value['object'] = $value;
		$this->item->set($value);
		return $this;
	}

	/**
	 *
	 * @return array[]
	 */
	private function compute_depends() {
		/* @var $depend ORM */
		$result = array();
		foreach ($this->depends as $id => $depend) {
			$result[$id] = $this->_compute_orm_state($depend);
		}
		return $result;
	}

	/**
	 *
	 * @return array[]
	 */
	private function compute_class_depends() {
		/* @var $depend Class_ORM */
		$result = array();
		foreach ($this->class_depends as $id => $depend) {
			$result[$id] = $this->_compute_class_orm_state($depend);
		}
		return $result;
	}

	/**
	 *
	 * @param ORM $depend
	 * @return number
	 */
	private function _compute_orm_state(ORM $depend) {
		$class_orm = $depend->class_orm();
		$columns = $class_orm->cache_column_names;
		sort($columns);
		$result = ArrayTools::flatten($depend->members($columns)) + array(
			"_columns" => $columns
		);
		$result['_hash'] = md5(serialize($result));
		return $result;
	}

	/**
	 *
	 * @param ORM $depend
	 * @return number
	 */
	private function _compute_class_orm_state(Class_ORM $class_orm) {
		$database = $class_orm->database();
		$table = $class_orm->table();
		$info = $database->table_information($table);
		if (!is_array($info)) {
			return null;
		}
		$result = ArrayTools::filter($info, array(
			Database::TABLE_INFO_CREATED,
			Database::TABLE_INFO_DATA_SIZE,
			Database::TABLE_INFO_ROW_COUNT,
			Database::TABLE_INFO_UPDATED
		));
		$result = ArrayTools::trim_clean(ArrayTools::flatten($result));
		if (count($result) === 0) {
			return null;
		}
		$result['_hash'] = md5(serialize($result));
		return $result;
	}

	/**
	 * Sets the expiration time for this cache item.
	 *
	 * @param \DateTimeInterface|null $expiration
	 *        	The point in time after which the item MUST be considered expired.
	 *        	If null is passed explicitly, a default value MAY be used. If none is set,
	 *        	the value should be stored permanently or for as long as the
	 *        	implementation allows.
	 *
	 * @return static The called object.
	 */
	public function expiresAt($expiration) {
		return $this->item->expiresAt($expiration);
	}

	/**
	 * Sets the expiration time for this cache item.
	 *
	 * @param int|\DateInterval|null $time
	 *        	The period of time from the present after which the item MUST be considered
	 *        	expired. An integer parameter is understood to be the time in seconds until
	 *        	expiration. If null is passed explicitly, a default value MAY be used.
	 *        	If none is set, the value should be stored permanently or for as long as the
	 *        	implementation allows.
	 *
	 * @return static The called object.
	 */
	public function expiresAfter($time) {
		return $this->item->expiresAfter($time);
	}

	/**
	 * Invalidate this cache object when an object changes
	 *
	 * Cron assists with cleaning out these objects in the background.
	 *
	 * @param ORM $object
	 * @return self
	 */
	final public function depends(ORM $object) {
		$class_orm = $object->class_orm();
		$columns = $class_orm->cache_column_names;
		if (count($columns) === 0) {
			throw new Exception_Semantics("{method}: {class} does not have cache_column_names set in {class_orm}", array(
				"method" => __METHOD__,
				"class" => get_class($object),
				"class_orm" => get_class($class_orm)
			));
		}
		$id = json_encode($object->id());
		$this->depends[get_class($object) . "-" . $id] = $object;
		$this->is_hit = null;
		return $this;
	}

	/**
	 *
	 * @param Class_ORM $class
	 * @return self
	 */
	final public function depends_table(Class_ORM $class) {
		$this->class_depends[get_class($class)] = $class;
		$this->is_hit = null;
		return $this;
	}
}
