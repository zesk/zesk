<?php

/**
 * @copyright &copy; 2016 Market Acumen, Inc.
 */
namespace zesk;

/**
 * Output all classes required in the application
 *
 * @category Database
 */
class Command_Classes extends Command_Base {
	protected $option_types = array(
		"format" => "string",
		"database" => "boolean",
		"table" => "boolean"
	);
	protected $option_help = array(
		"database" => "show database related to object",
		"table" => "show table related to object"
	);
	function run() {
		$application = $this->application;
		$classes = $application->orm_module()->all_classes();
		$objects_by_class = array();
		$is_table = false;
		$rows = array();
		$filters = array(
			"class"
		);
		if ($this->option_bool("database")) {
			$filters[] = "database";
		}
		if ($this->option_bool("table")) {
			$filters[] = "table";
		}
		foreach ($classes as $data) {
			$result = ArrayTools::filter($data, $filters);
			if (array_key_exists("database", $result)) {
				$result['database'] = aevalue($result, 'database', __('-default-'));
			}
			$rows[] = $result;
		}
		$format = $this->option("format");
		if ($format === "text" || empty($format)) {
			echo Text::format_table($rows);
		} else {
			$this->render_format($rows);
		}
		return 0;
	}
}
