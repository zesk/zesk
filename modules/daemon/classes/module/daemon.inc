<?php
/**
 * 
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class Module_Daemon extends Module {
	/**
	 * 
	 * @var string
	 */
	public $rundir = null;
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \zesk\Module::initialize()
	 */
	public function initialize() {
		parent::initialize();
		$runpath = zesk()->paths->data("run");
		$this->rundir = path($runpath, 'daemon');
		Directory::depend($this->rundir, 0700);
	}
	
	/**
	 * 
	 * @param Template $template
	 * @return string[][]
	 */
	protected function hook_system_panel(Template $template) {
		return array(
			'system/panel/daemon' => array(
				"title" => __("Daemons"),
				"module_class" => __CLASS__,
				"server_data_key" => __CLASS__ . "::process_database"
			)
		);
	}
	protected function hook_cron_minute() {
		global $zesk;
		/* @var $zesk Kernel */
		try {
			$server = Server::singleton();
			if ($server) {
				$database = $this->process_database();
				foreach ($database as $process => $settings) {
					$pid = $settings['pid'];
					$database[$process]['alive'] = $zesk->process->alive($pid);
				}
				$server->data(__CLASS__ . "::process_database", $database);
			}
		} catch (\Exception $e) {
		}
	}
	
	/**
	 * Unlock database
	 */
	private function _unlock_database() {
		$path = $this->_database_path();
		flock($path, LOCK_UN);
	}
	
	/**
	 * Retrieve database path
	 * 
	 * @return string
	 */
	private function _database_path() {
		return path($this->rundir, "daemon.db");
	}
	public function unlink_database() {
		unlink($this->_database_path());
	}
	/**
	 * Get/set daemon database
	 * 
	 * @param array $set
	 */
	public function process_database(array $database = null) {
		global $zesk;
		/* @var $zesk Kernel */
		$path = $this->_database_path();
		if ($database === null) {
			if (!is_file($path)) {
				return array();
			}
			$fp = fopen($path, "r");
			flock($fp, LOCK_SH);
			$database = unserialize(fread($fp, 1024 * 1024));
			if ($this->db_debug) {
				$zesk->logger->debug("Read database: {data}", array(
					'data' => JSON::encode($database)
				));
			}
			flock($fp, LOCK_UN);
			fclose($fp);
			return is_array($database) ? $database : array();
		} else {
			if (!is_file($path)) {
				if (count($database) === 0) {
					return array();
				}
				if (!file_put_contents($path, serialize($database))) {
					throw new Exception_File_Permission($path, "write");
				}
			} else {
				if (count($database) === 0) {
					unlink($path);
					return array();
				}
			}
			$fp = fopen($path, "a");
			flock($fp, LOCK_SH);
			ftruncate($fp, 0);
			fwrite($fp, serialize($database));
			flock($fp, LOCK_UN);
			fclose($fp);
			if ($this->db_debug) {
				$zesk->logger->debug("Wrote database: {data}", array(
					'data' => JSON::encode($database)
				));
			}
			return $database;
		}
	}
}