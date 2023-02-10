<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk;

use Throwable;

/**
 * Dump the database settings to a BASH or configuration file readable format
 * @param string $db The database name to dump
 * @category Database
 */
class Command_Export extends Command {
	protected array $option_types = [
		'name' => 'string', 'prefix' => 'string',
	];

	public function run(): int {
		$dbname = $this->option('name');
		$prefix = $this->option('prefix', 'DB_');

		try {
			$database = $this->application->databaseModule()->databaseRegistry($dbname, ['connect' => false]);
			$url = $database->url();
			$parts = URL::parse($url);
			echo 'DB_URL=' . $this->shellQuote($url) . "\n";
			echo 'DB_HOST=' . $this->shellQuote($parts['host'] ?? '') . "\n";
			echo 'DB_USER=' . $this->shellQuote($parts['user'] ?? '') . "\n";
			echo 'DB_PASSWORD=' . $this->shellQuote($parts['pass'] ?? '') . "\n";
			echo 'DB_NAME=' . $this->shellQuote(ltrim($parts['path'] ?? '', '/')) . "\n";
			return 0;
		} catch (Throwable $t) {
			echo 'DB_ERROR="' . $t::class . "\";\n";
			echo 'DB_MESSAGE="' . $this->shellQuote(substr($t->getMessage(), 0, 1024)) . "\";\n";
			return 1;
		}
	}
}
