<?php
/**
 *
 */
namespace zesk;

use zesk\ArrayTools;
use zesk\PHP;
use zesk\URL;

/**
 * Dump the database to a file for this application.
 * @category Database
 */
class Command_Database_Dump extends Command_Base {
	protected $load_modules = array(
		"database"
	);
	protected $option_types = array(
		"name" => "string",
		"echo" => "boolean",
		"file" => "boolean",
		"target" => "string",
		"compress" => "boolean",
		"no-compress" => "boolean",
		"dir" => "string",
		"arg-prefix" => "string",
		"arg-suffix" => "string",
		"tables" => "list",
		"*" => "string"
	);
	protected $option_help = array(
		"name" => "Database name to dump",
		"echo" => "Output the shell command to do the dump instead of running it",
		"file" => "Presence means write the output to a file instead of stdout",
		"target" => "Implies --file and sets the file output pattern",
		"compress" => "Explicitly compress the dump using gzip (new option)",
		"no-compress" => "(deprecated) Prevent compression of the archive",
		"dir" => "Place the file in this directory",
		"arg-prefix" => "Pass options to prefix command-line",
		"arg-suffix" => "Pass options to suffix command-line",
		"tables" => "List of tables to dump",
		"*" => "string"
	);

	/**
	 *
	 * {@inheritDoc}
	 * @see Command::run()
	 */
	function run() {
		$dbname = $this->option('name');
		$db = $this->application->database_registry($dbname);
		if (!$db) {
			$this->error("No such database for \"$dbname\"\n");
			return false;
		}
		list($binary, $args) = $db->shell_command(array(
			"sql-dump-command" => true,
			"tables" => $this->option_list("tables")
		));
		if ($this->has_option("arg-prefix")) {
			$args = array_merge($this->option_list('arg-prefix'), $args);
		}
		$full_command_path = $this->application->paths->which($binary);
		if (!$full_command_path) {
			$this->error("Unable to find shell $binary in system path:" . implode(", ", $this->application->paths->command()) . "\n");
			return false;
		}

		$suffix = "";
		$where = "";
		if ($this->option_bool('file') || $this->has_option('dir') || $this->has_option('target')) {
			$app_root = $this->application->path();
			$map = array(
				'database_name' => $db->url("name"),
				"database_host" => $db->url("host"),
				"database_port" => $db->url("port"),
				"database_user" => $db->url("user"),
				"zesk_application_root" => $app_root,
				"application_root" => $app_root
			);
			$dir = $this->option("dir", "sql-dumps");
			if ($this->has_option("target")) {
				$target = $this->option("target");
			} else {
				$target = path($dir, "{database_name}-{YYYY}-{MM}-{DD}_{hh}-{mm}.sql");
			}
			if (empty($target)) {
				$this->usage("Database target file not specified?");
				return -1;
			}
			if ($this->has_option("compress")) {
				$compress = true;
			} else {
				$compress = !$this->option_bool("no-compress", false);
			}
			$where = map($target, $map);
			$where = Timestamp::now()->format($where);
			$suffix = "";
			if ($compress || File::extension($where) === "gz") {
				if (!$compress) {
					$this->application->logger->warning("target dump file has .gz extension, so ignoring --compress flag");
				} else {
					$where .= ".gz";
				}
				$suffix .= " | gzip";
			}
			$where = Directory::make_absolute($app_root, $where);
			$suffix .= " > $where";
		}

		if ($this->has_option("arg-suffix")) {
			$args = array_merge($args, explode(" ", $this->option('arg-suffix')));
		}

		$command_line = $full_command_path . implode("", ArrayTools::prefix($args, " ")) . $suffix;
		if ($this->option_bool('echo')) {
			echo $command_line . "\n";
			return true;
		}
		if ($where) {
			echo "Outputting to $where ... ";
			exec($command_line);
			echo "done.\n";
		} else {
			PHP::requires('pcntl', true);
			$func = "pcntl_exec";
			$func($full_command_path, $args);
		}
		return true;
	}
}

