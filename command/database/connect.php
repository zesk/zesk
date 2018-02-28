<?php

/**
 *
 * @copyright &copy; 2017 Market Acumen, Inc.
 * @author kent
 *
 */
namespace zesk;

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
class Command_Database_Connect extends Command_Base {
	/**
	 *
	 * @var array
	 */
	protected $option_types = array(
		'name' => 'string',
		'echo' => 'boolean',
		'debug-connect' => 'boolean',
		'db-url' => 'boolean',
		'show-passwords' => 'boolean',
		'force' => 'boolean',
		'test' => 'boolean',
		'db-name' => 'boolean'
	);
	/**
	 *
	 * @var array
	 */
	protected $option_help = array(
		'name' => 'Database code name to connect to',
		'echo' => 'Output the connection command instead of running it',
		'debug-connect' => 'Show the connect command',
		'db-url' => 'Output the database URL or URLs',
		'force' => 'Force command execution even on failure',
		'test' => 'Test to make sure all connections work',
		'db-name' => 'Output the database name or names'
	);

	/**
	 *
	 * @return integer
	 */
	function run() {
		if ($this->option_bool("db-name") || $this->option_bool('db-url')) {
			return $this->handle_info();
		}
		if ($this->option_bool("test")) {
			return $this->handle_test();
		}

		if ($this->option_bool("grants")) {
			return $this->handle_grants();
		}

		$name = $this->option('name');
		$db = $this->application->database_registry($name);
		list($command, $args) = $db->shell_command($this->options);

		if ($this->option_bool('debug-connect')) {
			echo "$command " . implode(" ", $args) . "\n";
		}
		$full_command_path = is_file($command) ? $command : $this->application->paths->which($command);
		if (!$full_command_path) {
			die("Unable to find shell $command in system path:" . implode(", ", $this->application->paths->command()) . "\n");
		}
		if ($this->option_bool('echo')) {
			echo $full_command_path . implode("", ArrayTools::prefix($args, " ")) . "\n";
		} else {
			PHP::requires('pcntl', true);
			$method = 'pcntl_exec';
			$method($full_command_path, $args);
		}
		return 0;
	}

	/**
	 *
	 * @return number
	 */
	private function handle_info() {
		$name = $this->option('name');
		$db = $this->application->database_module()->register();
		if (!$this->option_bool("show-passwords")) {
			foreach ($db as $key => $url) {
				$db[$key] = URL::remove_password($url);
			}
		}
		if ($this->option_bool("db-name")) {
			foreach ($db as $key => $url) {
				$db[$key] = Database::url_parse($url, "name");
			}
		}
		if ($name) {
			if (array_key_exists($name, $db)) {
				echo $db[$name] . "\n";
				return 0;
			} else {
				$this->error("{name} not found", compact("name"));
				return -1;
			}
		}
		echo Text::format_pairs($db);
		return 0;
	}

	/**
	 *
	 * @return number
	 */
	private function handle_test() {
		$db = $this->application->database_module()->register();
		foreach ($db as $name => $url) {
			try {
				$this->application->database_registry($name);
				$db[$name] = true;
			} catch (Exception $e) {
				$db[$name] = false;
			}
		}
		echo Text::format_pairs($db);
		return 0;
	}

	/**
	 * TODO
	 */
	private function handle_grants() {
		$db = $this->application->database_module()->register();
		foreach ($db as $name => $url) {
		}
		echo Text::format_pairs($db);
		return 0;
	}
}
