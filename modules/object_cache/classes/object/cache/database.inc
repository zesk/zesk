<?php
/**
 * 
 */
use zesk\Database;
use zesk\Database_Schema;
use zesk\Database_Query_Select;

/**
 * 
 * @author kent
 *
 */
class Object_Cache_Database extends Object_Cache {

	/**
	 * @param Object $object
	 * @return Database
	 */
	private function cache_database(Object $object) {
		return $object->database();
	}

	private function _object_database_table(Database $database, Object $object, $table) {
		$table_schema = array(
			'columns' => array(
				'id' => array(
					'type' => 'varbinary(16)'
				),
				'key' => array(
					'type' => 'varbinary(16)'
				),
				'data' => array(
					'type' => 'text',
					'not null' => true
				)
			),
			'primary keys' => array(
				'id',
				'key'
			)
		);
		return Database_Schema::schema_to_database_table($database, $table, $table_schema);
	}

	private function object_table_name(Object $object) {
		return "Cache_" . $object->table();
	}

	private function object_table(Object $object, $create = false) {
		$table_name = $this->object_table_name($object);
		$database = $this->cache_database($object);
		if ($create) {
			$database->query(Database_Schema::synchronize($database, $this->_object_database_table($database, $object, $table_name)));
			return $table_name;
		}
		return $database->table_exists($table_name) ? $table_name : null;
	}

	public function load(Object $object, $key) {
		$table = $this->object_table($object);
		if (!$table) {
			return;
		}
		$db = $this->cache_database($object);
		$query = new zesk\Database_Query_Select($db);
		$query->from($table);
		$query->where("", $object->id());
		$hash = md5($key);
		$query->where("*key", "UNHEX(" . $db->quote_value($hash) . ")");
		$query->what("data");
		return unserialize($query->one("data", null));
	}

	public function save(Object $object, $key, $data) {
		$database = $this->cache_database($object);
		$table = $this->object_table($object, ($data !== null));
		if (!$table) {
			return false;
		}
		$hash = md5($key);
		$update = array(
			"*key" => "UNHEX(" . $database->quote_value($hash) . ")",
			"id" => $object->id()
		);
		$update['data'] = serialize($data);
		$sql = $database->sql()->insert(array(
			'table' => $table,
			'values' => $update,
			'verb' => "REPLACE"
		));
		$database->query($sql);
	}

	public function invalidate(Object $object, $key = null) {
		$table = $this->object_table($object);
		if (!$table) {
			return;
		}
		$database = $this->cache_database($object);
		$where = array(
			"id" => $object->id()
		);
		if ($key !== null) {
			$hash = md5($key);
			$where['*key'] = "UNHEX(" . $database->quote_value($hash) . ")";
		}
		$sql = $database->sql()->delete(array(
			'table' => $table,
			'where' => $where
		));

		$database->query($sql);
	}
}
