<?php

/**
 *
 */
namespace zesk;

/**
 * Scan all application database files and output any changes needed to the schema
 *
 * @aliases database-schema
 *
 * @category Database
 */
class Command_Schema extends Command_Base {
	/**
	 *
	 * @var array
	 */
	protected $option_types = array(
		"check" => "boolean",
		"url" => "string",
		"name" => "string",
		"update" => "boolean",
		"*" => "string",
	);

	/**
	 *
	 * @var array
	 */
	protected $register_classes = array();

	/**
	 *
	 * @var array
	 */
	protected $results = array();

	/**
	 *
	 * @return \zesk\multitype:
	 */
	public function results() {
		return $this->results;
	}

	/**
	 */
	protected function synchronize_before() {
		self::_synchronize_suffix("update");
	}

	/**
	 */
	protected function synchronize_after() {
		if ($this->option_bool("update")) {
			self::_synchronize_suffix("updated");
		}
	}

	/**
	 * Add calling callback log
	 *
	 * @param callable $callable
	 */
	public function hook_callback($callable) {
		$this->debug_log("{class}: Calling {callable}", array(
			"class" => __CLASS__,
			"callable" => $this->application->hooks->callable_string($callable),
		));
	}

	/**
	 * Invoke
	 *
	 * ORM::schema_updated
	 * ORM::schema_update
	 *
	 * Module::schema_updated
	 * Module::schema_update
	 *
	 * @param string $suffix
	 */
	private function _synchronize_suffix($suffix) {
		$hook_callback = array(
			$this,
			"hook_callback",
		);

		$app = $this->application;

		$hook_type = "zesk\ORM::schema_$suffix";
		$all_hooks = $this->application->hooks->find_all($hook_type);

		$app->logger->notice("Running all $suffix hooks {hooks}", array(
			"hooks" => ($all = implode(", ", array_values($all_hooks))) ? $all : "- no hooks found",
		));
		$this->application->hooks->all_call_arguments($hook_type, array(
			$this->application,
		), null, $hook_callback);

		$hook_type = "schema_$suffix";
		$all = $app->modules->all_hook_list($hook_type);
		$hooks_strings = array();
		if (count($all) !== 0) {
			$hooks_strings = array();
			foreach ($all as $hook) {
				$hooks_strings[] = $app->hooks->callable_string($hook);
			}
		}
		$app->logger->notice("Running module $suffix hooks {hooks}", array(
			"hooks" => $hooks_strings ? implode(", ", $hooks_strings) : "- no hooks found",
		));
		$app->modules->all_hook_arguments($hook_type, array(
			$this->application,
		), null, $hook_callback);

		$app_hooks = $app->hook_list($hook_type);
		$app_hooks_strings = "- no hooks found";
		if (count($app_hooks) !== 0) {
			$app_hooks_strings = array();
			foreach ($app_hooks as $hook) {
				$app_hooks_strings[] = $app->hooks->callable_string($hook);
			}
		}
		$app->logger->notice("Running application $suffix hooks {hooks}", array(
			"hooks" => $app_hooks_strings,
		));
		$app->call_hook_arguments($hook_type, array(
			$this->application,
		), null, $hook_callback);
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Command_Base::initialize()
	 */
	protected function initialize() {
		parent::initialize();
		$this->application->register_class("zesk\ORM_Schema_File");
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Command::run()
	 */
	protected function run() {
		$application = $this->application;

		if ($this->option_bool("debug")) {
			ORM_Schema::$debug = true;
		}
		$url = null;
		if ($this->has_option("url")) {
			$url = $this->option('url');
			if (!URL::valid($url)) {
				$this->usage("--url is not a valid URL, e.g. mysql://user:password@host/database");
			}
		} elseif ($this->has_option("name")) {
			$url = $this->option('name');
		}
		$classes = null;
		if ($this->has_arg()) {
			$classes = $this->arguments_remaining(true);
			$this->verbose_log("Running on classes {classes}", compact("classes"));
		}

		$this->synchronize_before();

		$database = $application->database_registry($url);
		$this->results = $results = $application->orm_module()->schema_synchronize($database, $classes, array(
			"check" => $this->option_bool('check'),
		));
		$suffix = ";\n";
		if ($this->option_bool('update')) {
			foreach ($results as $index => $sql) {
				try {
					$result = $database->query($sql);
					$this->results[$index] = array(
						"sql" => $sql,
						"result" => $result,
					);
					echo "$sql$suffix";
				} catch (Exception $e) {
					$this->results[$index] = array(
						"sql" => $sql,
						"exception" => $e,
					);
					echo "FAILED: $sql$suffix" . $e->getMessage() . "\n\n";
				}
			}
			$this->synchronize_after();
		} else {
			if (count($results) === 0) {
				return;
			}
			echo implode($suffix, ArrayTools::rtrim($results, $suffix)) . $suffix;
		}
	}
}
