<?php declare(strict_types=1);

/**
 *
 */
namespace zesk\Daemon;

use Psr\Cache\InvalidArgumentException;
use zesk\Directory;
use zesk\Exception_Configuration;
use zesk\Exception_Directory_Create;
use zesk\Exception_Directory_Permission;
use zesk\Exception_Key;
use zesk\Exception_Semantics;
use zesk\Exception_Syntax;
use zesk\Exception_Unsupported;
use zesk\ORM\Server;
use zesk\PHP;
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
	public string $runPath;

	private bool $db_debug = false;

	/**
	 *
	 *
	 * @return void
	 * @throws Exception_Configuration
	 * @throws Exception_Directory_Create
	 * @throws Exception_Directory_Permission
	 * @throws Exception_Unsupported
	 */
	public function initialize(): void {
		parent::initialize();
		$runPath = $this->application->dataPath('run');
		$this->runPath = path($runPath, 'daemon');
		Directory::depend($this->runPath, 0o700);
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
			$database = $this->loadProcessDatabase();
			foreach ($database as $process => $settings) {
				$pid = $settings['pid'];
				$database[$process]['alive'] = $application->process->alive($pid);
			}
			$server->setMeta(__CLASS__ . '::process_database', $database);
			$server->setMeta(__CLASS__ . '::process_database_updated', Timestamp::now());
		} catch (Exception_Syntax|Exception_File_Permission|Exception_Semantics|Exception_Key
		|InvalidArgumentException $e) {
			$this->application->logger->error($e->getRawMessage(), $e->variables());
		}
	}

	/**
	 * Retrieve database path
	 *
	 * @return string
	 */
	private function _databasePath(): string {
		return path($this->runPath, 'daemon.db');
	}

	public function unlink_database(): void {
		unlink($this->_databasePath());
	}

	/**
	 * Get/set daemon database
	 *
	 * @param array|null $database
	 * @return array
	 * @throws Exception_File_Permission
	 * @throws Exception_Semantics
	 */
	public function saveProcessDatabase(array $database): void {
		$path = $this->_databasePath();
		if (!is_file($path)) {
			if (count($database) === 0) {
				return;
			}
			if (!file_put_contents($path, serialize($database))) {
				throw new Exception_File_Permission($path, 'write');
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
	 * @throws Exception_File_Permission|Exception_Syntax
	 */
	public function loadProcessDatabase(): array {
		$path = $this->_databasePath();
		if (!is_file($path)) {
			return [];
		}
		$fp = fopen($path, 'rb');
		if (!$fp) {
			throw new Exception_File_Permission($path, "fopen($path) returned false-ish");
		}
		if (!flock($fp, LOCK_SH)) {
			fclose($fp);

			throw new Exception_File_Permission($path, "flock($path, LOCK_SH) returned false-ish");
		}
		$result = fread($fp, 1024 * 1024);
		if ($result === false) {
			fclose($fp);

			throw new Exception_File_Permission($path, "fread($path) returned false");
		}
		$database = PHP::unserialize($result);
		if ($this->db_debug) {
			try {
				$this->application->logger->debug('Read database: {data}', [
					'data' => JSON::encode($database),
				]);
			} catch (Exception_Semantics $e) {
				PHP::log($e);
			}
		}
		if (!flock($fp, LOCK_UN)) {
			fclose($fp);

			throw new Exception_File_Permission($path, "flock($path, LOCK_UN) returned false-ish");
		}
		fclose($fp);
		return is_array($database) ? $database : [];
	}

	/**
	 * Get/set daemon database
	 *
	 * @param array|null $database
	 * @return array
	 * @throws Exception_File_Permission
	 * @throws Exception_Semantics
	 */
	public function process_database(array $database = null): array {
		if ($database === null) {
			return $this->loadProcessDatabase();
		} else {
			$this->saveProcessDatabase($database);
			return $database;
		}
	}
}
