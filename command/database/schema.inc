<?php

/**
 * Scan all application database files and output any changes needed to the schema
 * @category Database
 * @param application Pass the application name after this parameter in order to invoke an alternate application
 * @aliases schema
 */
class Command_Database_Schema extends Command_Base {

	protected $option_types = array(
		"check" => "boolean",
		"url" => "string",
		"name" => "string",
		"update" => "boolean",
		"*" => "string"
	);

	protected $results = array();

	public function results() {
		return $this->results;
	}

	protected function synchronize_before() {
		self::_synchronize_suffix("update");
	}

	protected function synchronize_after() {
		if ($this->option_bool("update")) {
			self::_synchronize_suffix("updated");
		}
	}

	/**
	 * Invoke
	 *
	 * Object::schema_updated
	 * Object::schema_update
	 *
	 * Module::schema_updated
	 * Module::schema_update
	 *
	 * @param string $suffix
	 */
	private function _synchronize_suffix($suffix) {
		$hook_type = "Object::schema_$suffix";
		log::notice("Running all $suffix hooks {hooks}", array(
			"hooks" => ($all = implode(", ", array_values(zesk::find_all_hooks($hook_type)))) ? $all : "- no hooks found"
		));
		zesk::all_hook($hook_type, $this);

		$hook_type = "schema_$suffix";
		$all = Module::all_hook_list($hook_type);
		log::notice("Running module $suffix hooks {hooks}", array(
			"hooks" => $all ? $all : "- no hooks found"
		));
		Module::all_hook($hook_type, $this);

	}

	protected function run() {
		$application = Application::instance();

		Database_Schema::$debug = $this->option_bool('debug') || zesk::get('debug') || zesk::getb('Database_Schema::debug');

		if (Database_Schema::$debug) {
			log::level(999);
		}

		$url = null;
		if ($this->has_option("url")) {
			$url = $this->option('url');
			if (!url::valid($url)) {
				$this->usage("--url is not a valid URL, e.g. mysql://user:password@host/database");
			}
		} else if ($this->has_option("name")) {
			$url = $this->option('name');
		}
		$classes = null;
		if ($this->has_arg()) {
			$classes = $this->arguments_remaining(true);
			$this->verbose_log("Running on classes {classes}", compact("classes"));
		}

		$this->synchronize_before();

		$database = Database::factory($url);
		$this->results = $results = $application->schema_synchronize($database, $classes, array(
			"check" => $this->option_bool('check')
		));
		$suffix = ";\n";
		if ($this->option_bool('update')) {
			foreach ($results as $index => $sql) {
				try {
					$result = $database->query($sql);
					$this->results[$index] = array(
						"sql" => $sql,
						"result" => $result
					);
					echo "$sql$suffix";
				} catch (Exception $e) {
					$this->results[$index] = array(
						"sql" => $sql,
						"exception" => $e
					);
					echo "FAILED: $sql$suffix" . $e->getMessage() . "\n\n";
				}
			}
			$this->synchronize_after();
		} else {
			if (count($results) === 0) {
				return;
			}
			echo implode($suffix, arr::rtrim($results, $suffix)) . $suffix;
		}
	}
}