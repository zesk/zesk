<?php
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
	protected $option_types = array(
		"name" => "string",
		"yes" => "boolean",
		"file" => "file",
		"no-inf-fix" => "boolean",
		"dump-directory" => "dir"
	);
	protected $option_help = array(
		"name" => "Database to reset",
		'yes' => "Do not prompt the user to overwrite current database (reply yes to any prompts)",
		'file' => "Use the file specified as the database to restore (ignores dump-directory)",
		'no-inf-fix' => "Do not try to fix MySQL dumps which can not handle INFINITY in mysqldump",
		'dump-directory' => "Use alternate dump directory. Default is \"{default-dump-directory}\""
	);
	function __construct($argv = null) {
		$this->option_help = map($this->option_help, array(
			'default-dump-directory' => $this->default_dump_directory()
		));
		parent::__construct($argv);
	}
	function default_dump_directory() {
		return zesk::application_root('sql-dumps/');
	}
	function run() {
		zesk::php_has('pcntl', true);

		$dbname = $this->option('name');
		$db = Database::instance($dbname);
		if (!$db) {
			$this->error("No such database url for \"$dbname\"\n");
			return false;
		}
		$url = $db->url();
		$dbname = $db->database_name();
		$codename = $db->code_name();

		$live_db = $this->has_option('file') ? $this->option('file') : null;
		$has_dd = $this->has_option('dump-directory');
		if (!$live_db) {
			$dump_dir = $has_dd ? $this->option('dump-directory') : $this->default_dump_directory();
			$live_db = dir::ls($dump_dir, '#^' . $dbname . '.*\.sql\.gz$#', true);
			if (count($live_db) === 0) {
				$this->error("No dumps called $dbname found\n");
				exit(1);
			}
			sort($live_db, SORT_STRING);
			$live_db = array_pop($live_db);
		} else if ($has_dd) {
			log::warning("Specifying --file and --dump-directory are incompatible. --dump-diretory is ignored.");
		}
		if (!$this->option('yes')) {
			echo "Overwrite\n\t" . url::remove_password($url) . "\nwith\n\t" . $live_db . "? (y/N) ";
			$reply = fgets(STDIN, 8);
			if (to_bool(trim($reply)) !== true) {
				$this->error("Aborting.");
				return;
			}
		}
		$db_arg = ($codename !== "default") ? " --name \"$codename\"" : "";
		echo "Unpacking live database $live_db ... ";
		$command = "gunzip -c $live_db | sed \"s/1.79769313486232e+308/'1.79769313486232e+308'/g\" | zesk database-connect$db_arg";
		$this->verbose_log("Running command: $command");
		exec($command);
		echo " done\n";
	}
}
