<?php

/**
 * Scan all application database files and output any changes needed to the schema
 * @category Database
 * @param application Pass the application name after this parameter in order to invoke an alternate application
 */
class Command_Database_Schema extends Command_Base {

	protected $option_types = array(
		"url" => "string",
		"name" => "string",
		"*" => "string"
	);

	protected function run() {
		$application = Application::instance();

		Database_Schema::$debug = $this->option_bool('debug') || zesk::get('debug') || zesk::getb('Database_Schema::debug');

		$classes = null;
		if ($this->has_arg()) {
			$classes = $this->arguments_remaining(true);
		}
		$classes = $application->classes();


		if (Database_Schema::$debug) {
			log::level(999);
			log::file("php://stdout");
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

		$database = Database::factory($url);
		$results = $application->schema_synchronize($database, $classes);
		if (count($results) === 0) {
			return;
		}
		$suffix = ";\n";
		echo implode($suffix, arr::rtrim($results, $suffix)) . $suffix;
	}
}