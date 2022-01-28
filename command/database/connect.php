<?php declare(strict_types=1);

/**
 *
 * @copyright &copy; 2022 Market Acumen, Inc.
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
	protected array $option_types = [
		'name' => 'string',
		'format' => 'string',
		'echo' => 'boolean',
		'debug-connect' => 'boolean',
		'db-url' => 'boolean',
		'show-passwords' => 'boolean',
		'force' => 'boolean',
		'test' => 'boolean',
		'db-name' => 'boolean',
		'grant' => 'boolean',
		'host' => 'string',
	];

	/**
	 *
	 * @var array
	 */
	protected array $option_help = [
		'name' => 'Database code name to connect to',
		'echo' => 'Output the connection command instead of running it',
		'debug-connect' => 'Show the connect command',
		'db-url' => 'Output the database URL or URLs',
		'force' => 'Force command execution even on failure',
		'test' => 'Test to make sure all connections work',
		'db-name' => 'Output the database name or names',
		'grant' => 'Display grant statements for the database',
		'host' => 'Host used in grant statement if not otherwise supplied',
	];

	/**
	 *
	 * @return integer
	 */
	public function run() {
		if ($this->optionBool("db-name") || $this->optionBool('db-url')) {
			return $this->handle_info();
		}
		if ($this->optionBool("test")) {
			return $this->handle_test();
		}

		if ($this->optionBool("grant")) {
			return $this->handle_grants();
		}

		$name = $this->option('name');
		$db = $this->application->database_registry($name);
		[$command, $args] = $db->shell_command();

		if ($this->optionBool('debug-connect')) {
			echo "$command " . implode(" ", $args) . "\n";
		}
		$full_command_path = is_file($command) ? $command : $this->application->paths->which($command);
		if (!$full_command_path) {
			die("Unable to find shell $command in system path:" . implode(", ", $this->application->paths->command()) . "\n");
		}
		if ($this->optionBool('echo')) {
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
		if (!$this->optionBool("show-passwords")) {
			foreach ($db as $key => $url) {
				$db[$key] = URL::remove_password($url);
			}
		}
		if ($this->optionBool("db-name")) {
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
		$this->render_format($db);
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
		$this->render_format($db);
		return 0;
	}

	/**
	 * TODO
	 */
	private function handle_grants() {
		$dbmodule = $this->application->database_module();
		$db = $dbmodule->register();
		$result = [];
		foreach ($db as $name => $url) {
			$object = $dbmodule->database_registry($name, [
				"connect" => false,
			]);
			/* @var $object \zesk\Database */
			try {
				$grant_statements = $object->sql()->grant([
					"user" => $object->url('user'),
					"pass" => $object->url('pass'),
					"name" => $object->url('name'),
					"from_host" => $this->option("host"),
					"tables" => Database_SQL::SQL_GRANT_ALL,
				]);
			} catch (Exception_Parameter $e) {
				$grant_statements = null;
			}
			if (is_array($grant_statements)) {
				$result = array_merge($grant_statements, $result);
			}
		}
		echo ArrayTools::join_suffix($result, ";\n");
		return 0;
	}
}
