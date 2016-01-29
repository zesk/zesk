<?php
/**
 * Connect to the database for this application.
 * Optionally set a non-default database by adding --db-connect=alt_db_name
 * @category Database
 * @global boolean debug.db-connect Set this global to true to show command that would be executed (--set
 * debug.db-connect=1)
 * @global boolean db-connect Set this global to alternate database
 * @aliases db-connect connect conn
 */
class Command_Database_Connect extends Command {
	protected $option_types = array(
		'name' => 'string',
		'echo' => 'boolean',
		'list' => 'boolean',
		'show-passwords' => 'boolean',
		'force' => 'boolean'
	);
	protected $option_help = array(
		'name' => 'Database code name to connect to',
		'echo' => 'Output the connection command instead of running it',
		'list' => 'Output the list of',
		'force' => 'Force command execution even on failure'
	);
	function run() {
		$name = $this->option('name');
		if ($this->option_bool("list")) {
			$db = Database::register();
			if (!$this->option_bool("show-passwords")) {
				foreach ($db as $name => $url) {
					$db[$name] = url::remove_password($url);
				}
			}
			if ($name) {
				if (array_key_exists($name, $db)) {
					echo $db[$name] . "\n";	
				} else {
					$this->error("{name} not found", compact("name"));
					return -1;
				}
			}
			echo text::format_pairs($db);
			return 0;
		}
		$db = Database::factory($name);
		list($command, $args) = $db->shell_command($this->options);

		if (zesk::getb('debug.db-connect')) {
			echo "$command " . implode(" ", $args) . "\n";
		}
		$full_command_path = zesk::which($command);
		if (!$full_command_path) {
			die("Unable to find shell $command in system path:" . implode(", ", zesk::command_path()) . "\n");
		}
		if ($this->option_bool('echo')) {
			echo $full_command_path . implode("", arr::prefix($args, " ")) . "\n";
		} else {
			zesk::php_has('pcntl', true);
			$method = 'pcntl_exec';
			$method($full_command_path, $args);
		}
		return 0;
	}
}
