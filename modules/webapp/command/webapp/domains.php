<?php declare(strict_types=1);
namespace zesk\WebApp;

use zesk\File;

/**
 * List domains registered with Web Application module, or load them from a file.
 *
 * @category Web Application Manager
 * @author kent
 *
 */
class Command_WebApp_Domains extends \zesk\Command_Base {
	public array $option_types = [
		"file" => "file",
		"format" => "string",
	];

	public $option_help = [
		"file" => "Load domains from a file, one domain per line",
	];

	public function run() {
		if ($this->option("file")) {
			$lines = File::lines($this->option("file"));
			foreach ($lines as $line) {
				$line = trim($line);
				if (substr($line, 0, 1) === "#") {
					continue;
				}
				$this->application->orm_factory(Domain::class, [
					"name" => $line,
				])->register();
			}
		}
		$result = $this->application->orm_registry(Domain::class)
			->query_select()
			->what([
			"name" => "name",
			"active" => "active",
		])
			->order_by([
			"name",
			"active DESC",
		])
			->to_array("name", "active");
		return $this->render_format($result);
	}
}
