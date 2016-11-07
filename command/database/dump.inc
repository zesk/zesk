<?php
/**
 * Connect to the database for this application.
 * Optionally set a non-default database by adding --db-connect=alt_db_name
 * @category Database
 * @global boolean debug.db-connect Set this global to true to show command that would be executed (--set
 * debug.db-connect=1)
 * @global boolean db-connect Set this global to alternate database
 */
use zesk\File;
use zesk\Timestamp;

/**
 * Dump the database to stdout
 *
 * @param boolean $echo
 *        	Echo command that would be run instead of running it
 * @param string $db
 *        	The database name to dump
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
		global $zesk;
		/* @var $zesk zesk\Kernel */
		$dbname = $this->option('name');
		$db = $this->application->database_factory($dbname);
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
		$full_command_path = $zesk->paths->which($command);
		if (!$full_command_path) {
			$this->error("Unable to find shell $command in system path:" . implode(", ", $zesk->paths->command()) . "\n");
			return false;
		}
		
		$suffix = "";
		$where = "";
		if ($this->option_bool('file') || $this->has_option('dir')) {
			$app_root = $this->application->application_root();
			$map = array(
				'database_name' => $path,
				"database_host" => $host,
				"database_port" => $port,
				"database_user" => $user,
				"zesk_application_root" => $app_root,
				"application_root" => $app_root
			);
			$dir = $this->option("dir", "sql-dumps");
			/**
			 * @deprecated 2016-08
			 */
			if ($zesk->configuration->database_dump_file) {
				zesk()->deprecated();
				$zesk->logger->error("Global database_dump_file is deprecated, please fix it {line} {file}", array(
					"file" => __FILE__,
					"line" => __LINE__
				));
				$database_dump_file = $zesk->configuration->database_dump_file;
			} else if ($this->has_option("target")) {
				$database_dump_file = $this->option("target");
			} else {
				$database_dump_file = path($dir, "{database_name}-{YYYY}-{MM}-{DD}_{hh}-{mm}.sql");
			}
			if (empty($database_dump_file)) {
				$this->usage("Database dump file not specified?");
				return -1;
			}
			if ($this->has_option("compress")) {
				$compress = true;
			} else {
				$compress = !$this->option_bool("no-compress", false);
			}
			$where = map($database_dump_file, $map);
			$where = Timestamp::now()->format($where);
			$suffix = "";
			if ($compress || file::extension($where) === "gz") {
				if (!$compress) {
					$zesk->logger->warning("database_dump_file has .gz extension, so ignoring database_dump_compress flag");
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
		
		$command_line = $full_command_path . implode("", arr::prefix($args, " ")) . $suffix;
		if ($this->option_bool('echo')) {
			echo $command_line . "\n";
			return true;
		}
		if ($where) {
			echo "Outputting to $where ... ";
			exec($command_line);
			echo "done.\n";
		} else {
			zesk::php_has('pcntl', true);
			$func = "pcntl_exec";
			$func($full_command_path, $args);
		}
		return true;
	}
}

