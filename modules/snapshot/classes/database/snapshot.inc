<?php

/**
 * @see Class_Database_Snapshot
 * @author kent
 * @property id $id
 * @property string $name
 * @property string $dbname
 * @property string $created
 * @property string $data
 */
class Database_Snapshot extends Object {

	public static function temporary_path($add = null) {
		$path = zesk::temporary_path();
		dir::depend($path);
		return path($path, $add);
	}
	/**
	 * Create a database snapshot
	 *
	 * @param Database $db
	 * @throws Exception
	 * @return Database_Snapshot|NULL
	 */
	public static function backup(Database $db = null) {
		if ($db === null) {
			$db = Database::instance();
		}
		$now = Timestamp::now();
		$date = $now->format('{YYYY}-{MM}-{DD}_{hh}-{mm}-{ss}');

		$dbname = $db->database_name();
		$filename = "backup-$date-$dbname.sql";
		$temp = self::temporary_path($filename);
		try {
			$timer = new Timer();
			$db->dump($temp);
			$elapsed = $timer->elapsed();
			$snap = Object::factory(__CLASS__, array(
				'name' => __('{db_name} snapshot saved on {date}', array(
					'db_name' => $dbname,
					'date' => $date
				)),
				'dbname' => $dbname,
				'elapsed' => $elapsed,
				'created' => $now,
				'data' => file_get_contents($temp)
			))->store();
			file::unlink($temp);
			return $snap;
		} catch (Exception $e) {
			file::unlink($temp);
			throw $e;
		}
		return null;
	}

	public static function restore_latest(Database $db = null) {
		if ($db === null) {
			$db = Database::instance();
		}
		$dbname = $db->database_name();

		/* @var $snapshot Database_Snapshot */
		$snapshot = Object::class_query(__CLASS__)->what_object()
			->where(array(
			"dbname" => $dbname
		))
			->order_by('created DESC')
			->limit(0, 1)
			->one_object();
		if (!$snapshot) {
			return null;
		}
		return $snapshot->restore($db);
	}

	public function restore(Database $db = null) {
		if ($db === null) {
			$db = Database::instance();
		}
		if ($db->database_name() !== $this->dbname) {
			throw new Exception_Semantics("Restoring incorrect snapshot name {dbname} onto existing database name {db_database_name} - stopping", array(
				'db_database_name' => $db->database_name(),
				'dbname' => $this->dbname
			));
		}
		$now = Timestamp::now();
		$date = $now->format('{YYYY}-{MM}-{DD}_{hh}-{mm}-{ss}');
		$dbname = $db->database_name();
		$filename = "restore-$date-$dbname.sql";
		$temp = self::temporary_path($filename);
		file_put_contents($temp, $this->data);
		try {
			$db->restore($temp);
			file::unlink($temp);
			return $this;
		} catch (Exception $e) {
			log::error($e);
			file::unlink($temp);
			throw $e;
		}
		return null;
	}
}