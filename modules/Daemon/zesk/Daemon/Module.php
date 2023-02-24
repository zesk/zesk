<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage Daemon
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk\Daemon;

use Doctrine\ORM\Exception\ORMException;

use zesk\Cron\Attributes\CronMinute;
use zesk\Directory;
use zesk\Exception\DirectoryCreate;
use zesk\Exception\DirectoryPermission;
use zesk\Exception\FilePermission;
use zesk\Exception\ConfigurationException;
use zesk\Exception\Semantics;
use zesk\Exception\SyntaxException;
use zesk\Exception\Unsupported;
use zesk\JSON;
use zesk\Doctrine\Server;
use zesk\PHP;
use zesk\Timestamp;

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
	public string $runPath;

	private bool $db_debug = false;

	/**
	 *
	 *
	 * @return void
	 * @throws ConfigurationException
	 * @throws DirectoryCreate
	 * @throws DirectoryPermission
	 * @throws Unsupported
	 */
	public function initialize(): void {
		parent::initialize();
		$runPath = $this->application->dataPath('run');
		$this->runPath = Directory::path($runPath, 'daemon');
		Directory::depend($this->runPath, 0o700);
	}

	/**
	 * @todo This is not doing anything
	 *
	 * @return string[][]
	 */
	protected function hook_system_panel(): array {
		return [
			'system/panel/daemon' => [
				'title' => $this->application->locale->__('Daemons'),
				'moduleClass' => __CLASS__,
				'server_data_key' => __CLASS__ . '::process_database',
				'server_updated_key' => __CLASS__ . '::process_database_updated',
			],
		];
	}

	#[CronMinute]
	protected function hook_cron(): void {
		$application = $this->application;

		try {
			$server = Server::singleton($application);
			$database = $this->loadProcessDatabase();
			foreach ($database as $process => $settings) {
				$pid = $settings['pid'];
				$database[$process]['alive'] = $application->process->alive($pid);
			}
			$server->setMeta(__CLASS__ . '::process_database', $database);
			$server->setMeta(__CLASS__ . '::process_database_updated', Timestamp::now());
		} catch (ORMException $e) {
			$this->application->logger->error($e->getMessage());
		}
	}

	/**
	 * Retrieve database path
	 *
	 * @return string
	 */
	private function _databasePath(): string {
		return Directory::path($this->runPath, 'daemon.db');
	}

	public function unlink_database(): void {
		unlink($this->_databasePath());
	}

	/**
	 * @param array $database
	 * @return void
	 * @throws FilePermission
	 * @throws Semantics
	 */
	public function saveProcessDatabase(array $database): void {
		$path = $this->_databasePath();
		if (!is_file($path)) {
			if (count($database) === 0) {
				return;
			}
			if (!file_put_contents($path, serialize($database))) {
				throw new FilePermission($path, 'write');
			}
		} else {
			if (count($database) === 0) {
				unlink($path);
				return;
			}
		}
		$fp = fopen($path, 'ab');
		flock($fp, LOCK_SH);
		ftruncate($fp, 0);
		fwrite($fp, serialize($database));
		flock($fp, LOCK_UN);
		fclose($fp);
		if ($this->db_debug) {
			$this->application->logger->debug('Wrote database: {data}', [
				'data' => JSON::encode($database),
			]);
		}
	}

	/**
	 * Get daemon database
	 *
	 * @return array
	 * @throws FilePermission|SyntaxException
	 */
	public function loadProcessDatabase(): array {
		$path = $this->_databasePath();
		if (!is_file($path)) {
			return [];
		}
		$fp = fopen($path, 'rb');
		if (!$fp) {
			throw new FilePermission($path, "fopen($path) returned false-ish");
		}
		if (!flock($fp, LOCK_SH)) {
			fclose($fp);

			throw new FilePermission($path, "flock($path, LOCK_SH) returned false-ish");
		}
		$result = fread($fp, 1024 * 1024);
		if ($result === false) {
			fclose($fp);

			throw new FilePermission($path, "fread($path) returned false");
		}
		$database = PHP::unserialize($result);
		if ($this->db_debug) {
			try {
				$this->application->logger->debug('Read database: {data}', [
					'data' => JSON::encode($database),
				]);
			} catch (Semantics $e) {
				PHP::log($e);
			}
		}
		if (!flock($fp, LOCK_UN)) {
			fclose($fp);

			throw new FilePermission($path, "flock($path, LOCK_UN) returned false-ish");
		}
		fclose($fp);
		return is_array($database) ? $database : [];
	}
}
