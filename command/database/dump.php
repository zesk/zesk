<?php
/**
 *
 */
namespace zesk;

use zesk\Exception_NotFound;
use zesk\ArrayTools;
use zesk\PHP;
use zesk\URL;

/**
 * Dump the database to a file for this application.
 * @category Database
 */
class Command_Database_Dump extends Command_Base {
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
		"*" => "string"
	);
	private function map_db_scheme($scheme) {
		switch ($scheme) {
			case "mysql":
			case "mysqli":
				return "mysqldump";
			default :
				throw new Exception_NotFound($scheme);
		}
		return null;
	}
	
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
		$url = $db->url();
		$parts = $db->url_parse($url);
		$scheme = $host = $user = $pass = $path = $port = null;
		extract($parts, EXTR_IF_EXISTS);
		$args = array();
		if ($this->has_option("arg-prefix")) {
			$args = array_merge($args, explode(" ", $this->option('arg-prefix')));
		}
		if ($host) {
			$args[] = "-h";
			$args[] = $host;
		}
		if ($user) {
			$args[] = "-u";
			$args[] = $user;
		}
		if ($pass) {
			$args[] = "-p$pass";
		}
		$path = substr($path, 1);
		$args[] = $path;
		
		try {
			$command = self::map_db_scheme($scheme);
		} catch (Exception $e) {
			$url = URL::remove_password($url);
			$this->usage("No command-line shell command found for database type $scheme (URL: $url)");
		}
		$full_command_path = $this->application->paths->which($command);
		if (!$full_command_path) {
			$this->error("Unable to find shell $command in system path:" . implode(", ", $this->application->paths->command()) . "\n");
			return false;
		}
		
		$suffix = "";
		$where = "";
		if ($this->option_bool('file') || $this->has_option('dir') || $this->has_option('target')) {
			$app_root = $this->application->path();
			$map = array(
				'database_name' => $path,
				"database_host" => $host,
				"database_port" => $port,
				"database_user" => $user,
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

