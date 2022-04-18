<?php declare(strict_types=1);

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
	protected array $option_types = [
		"check" => "boolean",
		"url" => "string",
		"name" => "string",
		"update" => "boolean",
		"no-hooks" => "boolean",
		"*" => "string",
	];

	/**
	 *
	 * @var array
	 */
	protected array $register_classes = [];

	/**
	 *
	 * @var array
	 */
	protected array $results = [];

	/**
	 *
	 * @return array
	 */
	public function results() {
		return $this->results;
	}

	/**
	 */
	protected function synchronize_before(): void {
		if (!$this->optionBool("no-hooks")) {
			$this->_synchronize_suffix("update");
		}
	}

	/**
	 */
	protected function synchronize_after(): void {
		if ($this->optionBool("update")) {
			if (!$this->optionBool("no-hooks")) {
				$this->_synchronize_suffix("updated");
			}
		}
	}

	/**
	 * Add calling callback log
	 *
	 * @param callable $callable
	 */
	public function hook_callback($callable): void {
		$this->debug_log("{class}: Calling {callable}", [
			"class" => __CLASS__,
			"callable" => $this->application->hooks->callable_string($callable),
		]);
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
	private function _synchronize_suffix($suffix): void {
		$hook_callback = [
			$this,
			"hook_callback",
		];

		$app = $this->application;

		$hook_type = "zesk\ORM::schema_$suffix";
		$all_hooks = $this->application->hooks->find_all([$hook_type]);

		$app->logger->notice("Running all $suffix hooks {hooks}", [
			"hooks" => ($all = implode(", ", array_values($all_hooks))) ? $all : "- no hooks found",
		]);
		$this->application->hooks->all_call_arguments([$hook_type], [
			$this->application,
		], null, $hook_callback);

		$hook_type = "schema_$suffix";
		$all = $app->modules->all_hook_list($hook_type);
		$hooks_strings = [];
		if (count($all) !== 0) {
			$hooks_strings = [];
			foreach ($all as $hook) {
				$hooks_strings[] = $app->hooks->callable_string($hook);
			}
		}
		$app->logger->notice("Running module $suffix hooks {hooks}", [
			"hooks" => $hooks_strings ? implode(", ", $hooks_strings) : "- no hooks found",
		]);
		$app->modules->all_hook_arguments($hook_type, [
			$this->application,
		], null, $hook_callback);

		$app_hooks = $app->hook_list($hook_type);
		$app_hooks_strings = "- no hooks found";
		if (count($app_hooks) !== 0) {
			$app_hooks_strings = [];
			foreach ($app_hooks as $hook) {
				$app_hooks_strings[] = $app->hooks->callable_string($hook);
			}
		}
		$app->logger->notice("Running application $suffix hooks {hooks}", [
			"hooks" => $app_hooks_strings,
		]);
		$app->call_hook_arguments($hook_type, [
			$this->application,
		], null, $hook_callback);
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Command_Base::initialize()
	 */
	protected function initialize(): void {
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

		if ($this->optionBool("debug")) {
			ORM_Schema::$debug = true;
		}
		$url = null;
		if ($this->hasOption("url")) {
			$url = $this->option('url');
			if (!URL::valid($url)) {
				$this->usage("--url is not a valid URL, e.g. mysql://user:password@host/database");
			}
		} elseif ($this->hasOption("name")) {
			$url = $this->option('name');
		}
		$classes = null;
		if ($this->has_arg()) {
			$classes = $this->arguments_remaining(true);
			$this->verbose_log("Running on classes {classes}", compact("classes"));
		}

		$this->synchronize_before();

		$database = $application->database_registry($url);
		$this->results = $results = $application->orm_module()->schema_synchronize($database, $classes, [
			"skip_others" => true,
			"check" => $this->optionBool('check'),
		]);
		$suffix = ";\n";
		if ($this->optionBool('update')) {
			foreach ($results as $index => $sql) {
				try {
					$result = $database->query($sql);
					$this->results[$index] = [
						"sql" => $sql,
						"result" => $result,
					];
					echo "$sql$suffix";
				} catch (Exception $e) {
					$this->results[$index] = [
						"sql" => $sql,
						"exception" => $e,
					];
					echo "FAILED: $sql$suffix" . $e->getMessage() . "\n\n";
				}
			}
			$this->synchronize_after();
			return count($results) ? 1 : 0;
		} else {
			if (count($results) === 0) {
				return 0;
			}
			echo implode($suffix, ArrayTools::rtrim($results, $suffix)) . $suffix;
			return 1;
		}
	}
}
