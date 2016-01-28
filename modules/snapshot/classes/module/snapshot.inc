<?php

class Module_Snapshot extends Module {

	protected $classes = array(
		'Database_Snapshot'
	);

	// 	private static function _db_save_job(Database $db = null) {
	// 		if ($db === null) {
	// 			$db = Database::instance();
	// 		}
	// 		$db_name = $db->code_name();
	// 		return Job::instance(__("Creating new database snapshot for {db_name}"), "db-save-$db_name", __CLASS__ . "::job_db_save", array(
	// 			"db_name" => $db_name
	// 		));
	// 	}


	// 	public static function db_restore_job(Database $db = null) {
	// 		if ($db === null) {
	// 			$db = Database::instance();
	// 		}
	// 		$db_name = $db->code_name();
	// 		return Job::instance(__("Restoring database snapshot for {db_name}"), "db-restore-$db_name", __CLASS__ . "::job_db_restore", array(
	// 			"db_name" => $db_name
	// 		));
	// 	}


	// 	public static function job_db_save(Job $job, $shortname) {
	// 		Application_TimeBank::timebank_context($shortname);
	// 		Cache::disabled(true);
	// 		$app = Application::instance();
	// 		$cw3 = Database::instance('cw3');
	// 		$target = self::_db_restore_file($shortname);


	// 		$target_dir = dirname($target);
	// 		$temp_target = self::_db_restore_path("$shortname-temp.sql");
	// 		$job->progress(__("Backing up database ..."), 1);
	// 		try {
	// 			dir::depend($target_dir, 0775);
	// 			$cw3->dump($temp_target);
	// 			$job->progress(__("Backup completed ..."), 99);
	// 			file::move_atomic($temp_target, $target, self::_db_restore_path("$shortname-" . date('Y-m-d_H-i-s') . '.sql'));
	// 			$job->progress(__("Restore file is now updated ..."), 100);
	// 			$job->completed();
	// 		} catch (Exception $e) {
	// 			$job->progress(__('Backup of database failed ...'), 100);
	// 			log::error($e->getMessage());
	// 			$job->terminate();
	// 			file::unlink($temp_target);
	// 			return;
	// 		}
	// 	}


	// 	public static function job_db_restore(Job $job, $shortname) {
	// 		Application_TimeBank::timebank_context($shortname);
	// 		Cache::disabled(true);
	// 		$app = Application::instance();
	// 		$cw3 = Database::instance('cw3');
	// 		$target = self::_db_restore_file($shortname);
	// 		$job->progress(__("Restoring database ..."), 1);
	// 		try {
	// 			$cw3->restore($target);
	// 			$job->progress(__("Restored ... Clearing cache ..."), 90);
	// 			$app->cache_clear();
	// 			$job->progress(__("Done."), 100);
	// 			$job->completed();
	// 		} catch (Exception $e) {
	// 			$job->progress(__('Restoring of database failed ...'), 100);
	// 			log::error($e->getMessage());
	// 			$job->terminate();
	// 			return;
	// 		}
	// 	}


}
