<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 * Revert to a backup database
 *
 * Optionally set a non-default database by adding --db-connect=alt_db_name
 *
 * @category Database
 * @global boolean debug.db-connect Set this global to true to show command that would be executed (--set
 *         debug.db-connect=1)
 * @global boolean db-connect Set this global to alternate database
 * @aliases db-reset
 */
class Command_Database_Reset extends Command {
	protected array $option_types = [
		'name' => 'string',
		'yes' => 'boolean',
		'file' => 'file',
		'no-inf-fix' => 'boolean',
		'dump-directory' => 'dir',
	];

	protected array $option_help = [
		'name' => 'Database to reset',
		'yes' => 'Do not prompt the user to overwrite current database (reply yes to any prompts)',
		'file' => 'Use the file specified as the database to restore (ignores dump-directory)',
		'no-inf-fix' => 'Do not try to fix MySQL dumps which can not handle INFINITY in mysqldump',
		'dump-directory' => 'Use alternate dump directory. Default is "{default-dump-directory}"',
	];

	public function initialize(): void {
		$this->option_help = map($this->option_help, [
			'default-dump-directory' => $this->defaultDumpDirectory(),
		]);
	}

	public function defaultDumpDirectory(): string {
		return $this->application->path('sql-dumps/');
	}

	public function run(): int {
		PHP::requires('pcntl', true);

		$dbname = $this->option('name');
		$db = $this->application->database_registry($dbname);
		if (!$db) {
			$this->error("No such database url for \"$dbname\"\n");
			return self::EXIT_CODE_ARGUMENTS;
		}
		$url = $db->url();
		$dbname = $db->databaseName();
		$codename = $db->codeName();

		$live_db = $this->hasOption('file') ? $this->option('file') : null;
		$has_dd = $this->hasOption('dump-directory');
		if (!$live_db) {
			$dump_dir = $has_dd ? $this->option('dump-directory') : $this->defaultDumpDirectory();
			$live_db = Directory::ls($dump_dir, '#^' . $dbname . '.*\.sql\.gz$#', true);
			if (count($live_db) === 0) {
				$this->error("No dumps called $dbname found\n");
				exit(1);
			}
			sort($live_db, SORT_STRING);
			$live_db = array_pop($live_db);
		} elseif ($has_dd) {
			$this->application->logger->warning('Specifying --file and --dump-directory are incompatible. --dump-directory is ignored.');
		}
		if (!$this->option('yes')) {
			echo "Overwrite\n\t" . URL::removePassword($url) . "\nwith\n\t" . $live_db . '? (y/N) ';
			$reply = fgets(STDIN, 8);
			if (toBool(trim($reply)) !== true) {
				$this->error('Aborting.');
				return self::EXIT_CODE_ENVIRONMENT;
			}
		}
		$db_arg = ($codename !== 'default') ? " --name \"$codename\"" : '';
		echo "Unpacking live database $live_db ... ";
		$command = "gunzip -c $live_db | sed \"s/1.79769313486232e+308/'1.79769313486232e+308'/g\" | zesk connect$db_arg";
		$this->verboseLog("Running command: $command");
		exec($command);
		echo " done\n";
		return self::EXIT_CODE_SUCCESS;
	}
}
