<?php declare(strict_types=1);

/**
 *
 */
namespace zesk\Daemon;

use zesk\Directory;
use zesk\Server;
use zesk\Template;
use zesk\JSON;
use zesk\Timestamp;
use zesk\Exception_File_Permission;

/**
 *
 * @author kent
 *
 */
class Module extends \zesk\Module {
	/**
	 *
	 * @var string
	 */
	public $rundir = null;

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \zesk\Module::initialize()
	 */
	public function initialize(): void {
		parent::initialize();
		$runpath = $this->application->data_path('run');
		$this->rundir = path($runpath, 'daemon');
		Directory::depend($this->rundir, 0o700);
	}

	/**
	 *
	 * @param Template $template
	 * @return string[][]
	 */
	protected function hook_system_panel(Template $template) {
		$locale = $template->locale;
		return [
			'system/panel/daemon' => [
				'title' => $locale->__('Daemons'),
				'module_class' => __CLASS__,
				'server_data_key' => __CLASS__ . '::process_database',
				'server_updated_key' => __CLASS__ . '::process_database_updated',
			],
		];
	}

	protected function hook_cron(): void {
		$application = $this->application;

		try {
			$server = Server::singleton($application);
			if ($server) {
				$database = $this->process_database();
				foreach ($database as $process => $settings) {
					$pid = $settings['pid'];
					$database[$process]['alive'] = $application->process->alive($pid);
				}
				$server->data(__CLASS__ . '::process_database', $database);
				$server->data(__CLASS__ . '::process_database_updated', Timestamp::now());
			}
		} catch (\Exception $e) {
		}
	}

	/**
	 * Unlock database
	 */
	private function _unlock_database(): void {
		$path = $this->_database_path();
		flock($path, LOCK_UN);
	}

	/**
	 * Retrieve database path
	 *
	 * @return string
	 */
	private function _database_path() {
		return path($this->rundir, 'daemon.db');
	}

	public function unlink_database(): void {
		unlink($this->_database_path());
	}

	/**
	 * Get/set daemon database
	 *
	 * @param array $set
	 */
	public function process_database(array $database = null) {
		$path = $this->_database_path();
		$logger = $this->application->logger;
		if ($database === null) {
			if (!is_file($path)) {
				return [];
			}
			$fp = fopen($path, 'rb');
			flock($fp, LOCK_SH);
			$database = unserialize(fread($fp, 1024 * 1024));
			if ($this->db_debug) {
				$logger->debug('Read database: {data}', [
					'data' => JSON::encode($database),
				]);
			}
			flock($fp, LOCK_UN);
			fclose($fp);
			return is_array($database) ? $database : [];
		} else {
			if (!is_file($path)) {
				if (count($database) === 0) {
					return [];
				}
				if (!file_put_contents($path, serialize($database))) {
					throw new Exception_File_Permission($path, 'write');
				}
			} else {
				if (count($database) === 0) {
					unlink($path);
					return [];
				}
			}
			$fp = fopen($path, 'ab');
			flock($fp, LOCK_SH);
			ftruncate($fp, 0);
			fwrite($fp, serialize($database));
			flock($fp, LOCK_UN);
			fclose($fp);
			if ($this->db_debug) {
				$logger->debug('Wrote database: {data}', [
					'data' => JSON::encode($database),
				]);
			}
			return $database;
		}
	}
}
