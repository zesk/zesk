<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 * Dump the database settings to a BASH or configuration file readable format
 * @param string $db The database name to dump
 * @category Database
 */
class Command_Database_Export extends Command {
	protected array $option_types = [
		'name' => 'string',
	];

	public function run(): void {
		$dbname = $this->option('name');
		$url = $this->application->database_registry($dbname);
		if (!$url) {
			echo "DB_ERROR=\"Database_Connect_Failed\";\n";
			echo "DB_MESSAGE=\"Can not connect to '$dbname' - no URL configured\";\n";
			return;
		}
		$parts = parse_url($url);

		echo 'DB_URL=' . $this->shell_quote($url) . "\n";
		echo 'DB_HOST=' . $this->shell_quote(avalue($parts, 'host', '')) . "\n";
		echo 'DB_USER=' . $this->shell_quote(avalue($parts, 'user', '')) . "\n";
		echo 'DB_PASSWORD=' . $this->shell_quote(avalue($parts, 'pass', '')) . "\n";
		echo 'DB_NAME=' . $this->shell_quote(ltrim(avalue($parts, 'path', ''), '/')) . "\n";
	}
}
