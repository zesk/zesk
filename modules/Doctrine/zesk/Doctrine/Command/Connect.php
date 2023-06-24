<?php
declare(strict_types=1);
/**
 *
 * @copyright &copy; 2023, Market Acumen, Inc.
 * @author kent
 *
 */

namespace zesk\Doctrine\Command;

use Doctrine\DBAL\Connection;
use Throwable;
use zesk\ArrayTools;
use zesk\Command\SimpleCommand;
use zesk\Exception\CommandFailed;
use zesk\Exception\DirectoryCreate;
use zesk\Exception\DirectoryPermission;
use zesk\Exception\KeyNotFound;
use zesk\Exception\NotFoundException;
use zesk\Exception\UnsupportedException;
use zesk\PHP;

/**
 * Connect to the database for this application.
 * Optionally set a non-default database by adding --db-connect=alt_db_name
 *
 * @aliases db-connect connect conn
 *
 * @category Database
 * @global boolean debug.db-connect Set this global to true to show command that would be executed
 *         (--set debug.db-connect=1)
 * @global boolean db-connect Set this global to alternate database
 */
class Connect extends SimpleCommand {
	protected array $shortcuts = ['connect'];

	/**
	 *
	 * @var array
	 */
	protected array $option_types = [
		'name' => 'string', 'format' => 'string', 'echo' => 'boolean', 'debug-connect' => 'boolean',
		'db-url' => 'boolean', 'show-passwords' => 'boolean', 'force' => 'boolean', 'test' => 'boolean',
		'db-name' => 'boolean', 'grant' => 'boolean', 'host' => 'string',
	];

	/**
	 *
	 * @var array
	 */
	protected array $option_help = [
		'name' => 'Database code name to connect to', 'echo' => 'Output the connection command instead of running it',
		'debug-connect' => 'Show the connect command', 'db-url' => 'Output the database URL or URLs',
		'force' => 'Force command execution even on failure', 'test' => 'Test to make sure all connections work',
		'db-name' => 'Output the database name or names', 'grant' => 'Display grant statements for the database',
		'host' => 'Host used in grant statement if not otherwise supplied',
	];

	/**
	 *
	 * @return integer
	 * @throws CommandFailed
	 * @throws NotFoundException
	 * @throws UnsupportedException
	 * @throws KeyNotFound
	 */
	public function run(): int {
		if ($this->optionBool('db-name') || $this->optionBool('db-url')) {
			return $this->handle_info();
		}
		if ($this->optionBool('test')) {
			return $this->handle_test();
		}

		if ($this->optionBool('grant')) {
			return $this->handle_grants();
		}

		$name = $this->optionString('name');
		$db = $this->application->entityManager()->getConnection();

		[$command, $args] = $this->databaseShellCommand($db);

		if ($this->optionBool('debug-connect')) {
			echo "$command " . implode(' ', $args) . "\n";
		}
		$full_command_path = is_file($command) ? $command : $this->application->paths->which($command);
		if (!$full_command_path) {
			die("Unable to find shell $command in system path:" . implode(', ', $this->application->paths->command()) . "\n");
		}
		if ($this->optionBool('echo')) {
			echo $full_command_path . implode('', ArrayTools::prefixValues($args, ' ')) . "\n";
		} else {
			PHP::requires(PHP::FEATURE_PROCESS_CONTROL, true);
			$method = 'pcntl_exec';
			$method($full_command_path, $args);
		}
		return 0;
	}

	public function databaseShellCommand(Connection $connection): array {
//		$platform = $connection->getDatabasePlatform();
//		if (!$platform) {
//			throw new NotFoundException("No database platform for connection");
//		}
//		$parts = $connection->getParams();
//		$platformClass = strtolower(get_class($platform));
//		if (str_contains($platformClass, 'mysql')) {
//			return []
//
//		}
		return []; // TODO
	}

	private static array $shell_command_options = [
		'sql-dump-command' => 'boolean. Generate a command-line SQL dump command instead of a connection command',
		'tables' => 'string[]. Used in conjunction with sql-dump-command - an array of tables to dump',
		'non-blocking' => 'boolean. Used in conjunction with sql-dump-command - dump database in a non-blocking manner.',
	];

	public function mysqlShellCommand(array $options = []): array {
		/**
		 * @param array $options
		 * @return array
		 * @throws DirectoryCreate
		 * @throws DirectoryPermission
		 */
		foreach ($options as $option_key => $option_value) {
			if (!array_key_exists($option_key, self::$shell_command_options)) {
				$this->application->logger->warning('Unknown option passed to {method}: {option_key}={option_value}', [
					'method' => __METHOD__, 'option_key' => $option_key, 'option_value' => _dump($option_value),
				]);
			}
		}

		$parts = $this->url_parts;
		$host = $parts['host'] ?? null;
		$user = $parts['user'] ?? null;
		$pass = $parts['pass'] ?? null;
		$path = $parts['path'] ?? '';
		$args = [];
		if ($user && $pass) {
			if ($this->optionBool('password-on-command-line')) {
				$args[] = '-u';
				$args[] = $user;
				$args[] = "-p$pass";
			} else {
				$args[] = '--defaults-extra-file=' . $this->credentialsFile($user, $pass);
			}
		}
		if ($host) {
			$args[] = '-h';
			$args[] = $host;
		}
		if (toBool($options['force'] ?? false)) {
			$args[] = '-f';
		}
		$path = substr($path, 1);
		$args[] = $path;

		$bin = 'mysql';
		if (toBool($options['sql-dump-command'] ?? false)) {
			$bin = 'mysqldump';
			if (isset($options['non-blocking']) && toBool($options['non-blocking'])) {
				$args = array_merge($args, [
					'--single-transaction=TRUE',
				]);
			}
			$tables = toList($options['tables'] ?? []);
			$args = array_merge($args, $tables);
		}
		return [
			$bin, $args,
		];
	}

	/**
	 *
	 * @return int
	 * @throws KeyNotFound
	 */
	private function handle_info(): int {
		$name = $this->option('name');
		$dbs = $this->application->databaseModule()->databases();
		if (!$this->optionBool('show-passwords')) {
			foreach ($dbs as $db) {
				$dbs[$db->codeName()] = $db->safeURL();
			}
		}
		if ($this->optionBool('db-name')) {
			foreach ($dbs as $db) {
				$dbs[$db->codeName()] = $db->urlComponent('name');
			}
		}
		if ($name) {
			if (array_key_exists($name, $dbs)) {
				echo $dbs[$name] . "\n";
				return 0;
			} else {
				$this->error('{name} not found', compact('name'));
				return -1;
			}
		}
		$this->renderFormat($dbs);
		return 0;
	}

	/**
	 *
	 * @return int
	 */
	private function handle_test(): int {
		$db = [];
		foreach ($this->application->databaseModule()->names() as $name) {
			try {
				$this->application->databaseRegistry($name)->connect();
				$db[$name] = true;
			} catch (Throwable) {
				$db[$name] = false;
			}
		}
		$this->renderFormat($db);
		return 0;
	}

	/**
	 * TODO
	 */
	private function handle_grants(): int {
		$module = $this->application->databaseModule();
		$result = [];
		foreach ($module->names() as $name) {
			try {
				$object = $module->databaseRegistry($name, [
					'connect' => false,
				]);

				try {
					$grant_statements = $object->sqlDialect()->grant([
						'user' => $object->urlComponent('user'), 'pass' => $object->urlComponent('pass'),
						'name' => $object->urlComponent('name'), 'from_host' => $this->option('host'),
						'tables' => SQLDialect::SQL_GRANT_ALL,
					]);
				} catch (UnsupportedException|KeyNotFound) {
					$grant_statements = null;
				}
				if (is_array($grant_statements)) {
					$result = array_merge($grant_statements, $result);
				}
			} catch (NotFoundException|ConnectException $e) {
				$this->error($e->getMessage());
				return self::EXIT_CODE_ENVIRONMENT;
			}
		}
		echo ArrayTools::joinSuffix($result, ";\n");
		return 0;
	}
}
