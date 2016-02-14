<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/database/query/select/base.inc $
 * @package zesk
 * @subpackage system
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2010, Market Ruler, LLC
 */
abstract class Database_Query_Select_Base extends Database_Query {
	protected $objects_prefixes = array();
	function __sleep() {
		return array_merge(parent::__sleep(), array(
			"objects_prefixes"
		));
	}

	/**
	 * Convert this query into an iterator
	 *
	 * @param string $key
	 *        	Use this field as a key for the iterator
	 * @param string $value
	 *        	Use this column as a value for the iterator, null means use entire object/row
	 * @return Database_Result_Iterator
	 */
	function iterator($key = null, $value = null) {
		return new Database_Result_Iterator($this, $key, $value);
	}

	/**
	 * Convert this query into an Object Iterator
	 *
	 * @param string $class
	 *        	Class to iterate on (inherited from default settings for this query)
	 * @param array $options
	 *        	Options passed to each object upon creation
	 * @return Object_Iterator
	 */
	function object_iterator($class = null, $options = false) {
		$this->object_class($class);
		return new Object_Iterator($this->class, $this, $options);
	}

	/**
	 * Convert this query into an Objects Iterator
	 *
	 * @param string $class
	 *        	Class to iterate on (inherited from default settings for this query)
	 * @param array $options
	 *        	Options passed to each object upon creation
	 * @return Object_Iterator
	 */
	function objects_iterator(array $options = array()) {
		return new Objects_Iterator($this->class, $this, $this->objects_prefixes);
	}

	/**
	 * Execute query and retrieve a single field or row
	 *
	 * @param unknown_type $field
	 * @param unknown_type $default
	 * @return unknown
	 */
	function one($field = false, $default = false) {
		return $this->database()->query_one($this->__toString(), $field, $default, $this->db);
	}

	/**
	 * Execute query and return the first returned row as an object
	 *
	 * @param string $class
	 *        	An optional class to return the first row as
	 * @param array $options
	 *        	Optional options to be passed to the object upon instantiation
	 * @return Object
	 */
	function one_object($class = null, $options = null) {
		$columns = $this->one(false, null);
		if ($columns === null) {
			return null;
		}
		$options['from_database'] = true;
		return $this->object_factory($this->object_class($class), $columns, $options);
	}

	/**
	 * Execute query and convert to an object
	 *
	 * @param string $class
	 *        	Class of object
	 * @param unknown_type $default
	 * @return unknown
	 */
	function object($class = null, $options = false) {
		$result = $this->one(false, null);
		if ($result === null) {
			return $result;
		}
		$object = $this->object_factory($this->object_class($class), null, $options);
		$object->initialize($result, true);
		return $object;
	}

	/**
	 * This method should be overriden in subclasses if it has class_object support
	 *
	 * @param string $class
	 * @return string
	 */
	public function class_alias($class) {
		return "";
	}

	/**
	 * Append "what" fields for an entire object class, with $prefix before it, using alias $alias
	 *
	 * @param string $class
	 *        	Class to add what fields for; if not supplied uses the class associated with the
	 *        	query
	 * @param string $alias
	 *        	the alias associated with the class query, uses default (X) if not supplied
	 * @param string $prefix
	 *        	Prefix all output field names with this string, blank for nothing
	 * @param string $object_mixed
	 *        	Pass to class_table_columns for dynamic table objects
	 * @param string $object_options
	 *        	Pass to class_table_columns for dynamic table objects
	 * @return Databasse_Query_Select @ return Databasse_Query_Select_Base
	 */
	function what_object($class = null, $alias = null, $prefix = null, $object_mixed = null, $object_options = null) {
		if (!$class) {
			$class = $this->object_class();
		}
		if ($alias === null) {
			$alias = $this->class_alias($class);
		}

		$columns = Object::class_table_columns($class, $object_mixed, $object_options);
		$g = $this->db->sql();
		$what = array();
		foreach ($columns as $column) {
			$what[$prefix . $column] = "$alias.$column";
		}
		$this->objects_prefixes[$prefix] = array(
			$alias,
			$class
		);
		return $this->what($what, true);
	}

	/**
	 * Execute query and retrieve a single field, a Timestamp
	 *
	 * @param string $field
	 *        	Field to retrieve
	 * @param mixed $default
	 *        	Default value to retrieve
	 * @return Timestamp
	 */
	function one_integer($field = 0, $default = 0) {
		return $this->integer($field, $default);
	}

	/**
	 * Execute query and retrieve a single field, an integer
	 *
	 * @param string $field
	 *        	Field to retrieve
	 * @param mixed $default
	 *        	Default value to retrieve
	 * @return integer
	 */
	function one_timestamp($field = 0, $default = null) {
		return $this->timestamp($field, $default);
	}

	/**
	 * Execute query and retrieve a single field, a double
	 *
	 * @param string $field
	 *        	Field to retrieve
	 * @param mixed $default
	 *        	Default value to retrieve
	 * @return integer
	 */
	function double($field = 0, $default = null) {
		return to_double($this->one($field), $default);
	}

	/**
	 * Execute query and retrieve a single field, an integer
	 *
	 * @param string $field
	 *        	Field to retrieve
	 * @param mixed $default
	 *        	Default value to retrieve
	 * @return integer
	 */
	function integer($field = 0, $default = 0) {
		return $this->database()->query_integer($this->__toString(), $field, $default);
	}

	/**
	 * Execute query and retrieve a single field, a Timestamp
	 *
	 * @param string $field
	 *        	Field to retrieve
	 * @param mixed $default
	 *        	Default value to retrieve
	 * @return Timestamp
	 */
	function timestamp($field = 0, $default = null, DateTimeZone $timezone = null) {
		$value = $this->database()->query_one($this->__toString(), $field, $default);
		if (empty($value)) {
			$value = null;
		}
		return new Timestamp($value, $timezone);
	}

	/**
	 *
	 * @param string $key
	 * @param string $value
	 * @param unknown $default
	 * @return Ambigous <multitype:, mixed, multitype:mixed unknown >
	 */
	function to_array($key = null, $value = null, $default = array()) {
		return $this->database()->query_array($this->__toString(), $key, $value, $default);
	}

	/**
	 */
	abstract function __toString();
}
