<?php declare(strict_types=1);
/**
 *
 */
namespace zesk\ObjectCache;

use zesk\ORM;
use zesk\PHP;
use zesk\ORM_Schema;
use zesk\Database_Query_Select;

/**
 *
 * @author kent
 *
 */
class Database extends Base {
	/**
	 * @param ORM $object
	 * @return \zesk\Database
	 */
	private function cache_database(ORM $object) {
		return $object->database();
	}

	private function _object_database_table(\zesk\Database $database, ORM $object, $table) {
		$table_schema = [
			'columns' => [
				'id' => [
					'type' => 'varbinary(16)',
				],
				'key' => [
					'type' => 'varbinary(16)',
				],
				'data' => [
					'type' => 'text',
					'not null' => true,
				],
			],
			'primary keys' => [
				'id',
				'key',
			],
		];
		return ORM_Schema::schema_to_database_table($database, $table, $table_schema);
	}

	private function object_table_name(ORM $object) {
		return "Cache_" . $object->table();
	}

	private function object_table(ORM $object, $create = false) {
		$table_name = $this->object_table_name($object);
		$database = $this->cache_database($object);
		if ($create) {
			$database->query(ORM_Schema::synchronize($database, $this->_object_database_table($database, $object, $table_name)));
			return $table_name;
		}
		return $database->table_exists($table_name) ? $table_name : null;
	}

	public function load(ORM $object, $key) {
		$table = $this->object_table($object);
		if (!$table) {
			return;
		}
		$db = $this->cache_database($object);
		$query = new Database_Query_Select($db);
		$query->from($table);
		$query->where("", $object->id());
		$hash = md5($key);
		$query->where("*key", "UNHEX(" . $db->quote_text($hash) . ")");
		$query->what("data");
		return PHP::unserialize($query->one("data", null));
	}

	public function save(ORM $object, $key, $data) {
		$database = $this->cache_database($object);
		$table = $this->object_table($object, ($data !== null));
		if (!$table) {
			return false;
		}
		$hash = md5($key);
		$update = [
			"*key" => "UNHEX(" . $database->quote_text($hash) . ")",
			"id" => $object->id(),
		];
		$update['data'] = serialize($data);
		$sql = $database->sql()->insert([
			'table' => $table,
			'values' => $update,
			'verb' => "REPLACE",
		]);
		$database->query($sql);
	}

	public function invalidate(ORM $object, $key = null): void {
		$table = $this->object_table($object);
		if (!$table) {
			return;
		}
		$database = $this->cache_database($object);
		$where = [
			"id" => $object->id(),
		];
		if ($key !== null) {
			$hash = md5($key);
			$where['*key'] = "UNHEX(" . $database->quote_text($hash) . ")";
		}
		$sql = $database->sql()->delete([
			'table' => $table,
			'where' => $where,
		]);

		$database->query($sql);
	}
}
