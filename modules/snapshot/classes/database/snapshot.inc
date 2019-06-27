<?php

/**
 *
 */
namespace zesk;

/**
 *
 * @see Class_Database_Snapshot
 * @author kent
 * @property id $id
 * @property string $name
 * @property string $dbname
 * @property string $created
 * @property string $data
 */
class Database_Snapshot extends ORM {
	/**
	 *
	 * @param Application $application
	 * @param unknown $add
	 * @return string
	 */
	public static function temporary_path(Application $application, $add = null) {
		$path = $application->paths->temporary();
		Directory::depend($path);
		return path($path, $add);
	}

	/**
	 * Create a database snapshot
	 *
	 * @param Database $db
	 * @throws Exception
	 * @return Database_Snapshot|NULL
	 */
	public static function backup(Database $db) {
		$app = $db->application;
		$now = Timestamp::now();
		$date = $now->format(null, '{YYYY}-{MM}-{DD}_{hh}-{mm}-{ss}');

		$dbname = $db->database_name();
		$filename = "backup-$date-$dbname.sql";
		$temp = self::temporary_path($db->application, $filename);

		try {
			$timer = new Timer();
			$db->dump($temp);
			$elapsed = $timer->elapsed();
			$snap = $app->orm_factory(__CLASS__, array(
				'name' => __('{db_name} snapshot saved on {date}', array(
					'db_name' => $dbname,
					'date' => $date
				)),
				'dbname' => $dbname,
				'elapsed' => $elapsed,
				'created' => $now,
				'data' => file_get_contents($temp)
			))->store();
			File::unlink($temp);
			return $snap;
		} catch (Exception $e) {
			File::unlink($temp);

			throw $e;
		}
		return null;
	}
	public static function restore_latest(Database $db) {
		$dbname = $db->database_name();

		/* @var $snapshot Database_Snapshot */
		$snapshot = $db->application->orm_registry(__CLASS__)
			->query_select()
			->what_object()
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
			$db = $this->application->database_registry();
		}
		if ($db->database_name() !== $this->dbname) {
			throw new Exception_Semantics("Restoring incorrect snapshot name {dbname} onto existing database name {db_database_name} - stopping", array(
				'db_database_name' => $db->database_name(),
				'dbname' => $this->dbname
			));
		}
		$now = Timestamp::now();
		$date = $now->format(null, '{YYYY}-{MM}-{DD}_{hh}-{mm}-{ss}');
		$dbname = $db->database_name();
		$filename = "restore-$date-$dbname.sql";
		$temp = self::temporary_path($db->application, $filename);
		file_put_contents($temp, $this->data);

		try {
			$db->restore($temp);
			File::unlink($temp);
			return $this;
		} catch (Exception $e) {
			$this->application->logger->error($e);
			File::unlink($temp);

			throw $e;
		}
		return null;
	}
}
