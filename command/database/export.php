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

		echo 'DB_URL=' . $this->shellQuote($url) . "\n";
		echo 'DB_HOST=' . $this->shellQuote(avalue($parts, 'host', '')) . "\n";
		echo 'DB_USER=' . $this->shellQuote(avalue($parts, 'user', '')) . "\n";
		echo 'DB_PASSWORD=' . $this->shellQuote(avalue($parts, 'pass', '')) . "\n";
		echo 'DB_NAME=' . $this->shellQuote(ltrim(avalue($parts, 'path', ''), '/')) . "\n";
	}
}
