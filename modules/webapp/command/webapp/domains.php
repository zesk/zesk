<?php
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
	public $option_types = array(
		"file" => "file",
		"format" => "string",
	);

	public $option_help = array(
		"file" => "Load domains from a file, one domain per line",
	);

	public function run() {
		if ($this->option("file")) {
			$lines = File::lines($this->option("file"));
			foreach ($lines as $line) {
				$line = trim($line);
				if (substr($line, 0, 1) === "#") {
					continue;
				}
				$this->application->orm_factory(Domain::class, array(
					"name" => $line,
				))->register();
			}
		}
		$result = $this->application->orm_registry(Domain::class)
			->query_select()
			->what(array(
			"name" => "name",
			"active" => "active",
		))
			->order_by(array(
			"name",
			"active DESC",
		))
			->to_array("name", "active");
		return $this->render_format($result);
	}
}
