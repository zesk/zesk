<?php
/**
 * 
 */
use zesk\arr;
use zesk\URL;

/**
 * Scan all application database files and output any changes needed to the schema
 * @category Database
 * @param application Pass the application name after this parameter in order to invoke an alternate application
 * @aliases schema
 */
class Command_Database_Schema extends Command_Base {
	/**
	 *
	 * @var array
	 */
	protected $option_types = array(
		"check" => "boolean",
		"url" => "string",
		"name" => "string",
		"update" => "boolean",
		"*" => "string"
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
			"callable" => zesk()->hooks->callable_string($callable)
		));
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
		global $zesk;
		$hook_callback = array(
			$this,
			"hook_callback"
		);
		
		/* @var $zesk zesk\Kernel */
		$hook_type = "zesk\Object::schema_$suffix";
		$all_hooks = $zesk->hooks->find_all($hook_type);
		
		$zesk->logger->notice("Running all $suffix hooks {hooks}", array(
			"hooks" => ($all = implode(", ", array_values($all_hooks))) ? $all : "- no hooks found"
		));
		$zesk->hooks->all_call_arguments($hook_type, array(
			$this
		), null, $hook_callback);
		
		$hook_type = "schema_$suffix";
		$app = $this->application;
		$all = $app->modules->all_hook_list($hook_type);
		$zesk->logger->notice("Running module $suffix hooks {hooks}", array(
			"hooks" => $all ? $all : "- no hooks found"
		));
		$app->modules->all_hook_arguments($hook_type, array(
			$this
		), null, $hook_callback);
		
		$app_hooks = $app->hook_list($hook_type);
		$zesk->logger->notice("Running application $suffix hooks {hooks}", array(
			"hooks" => $app_hooks ? $app_hooks : "- no hooks found"
		));
		$app->call_hook_arguments($hook_type, array(
			$this
		), null, $hook_callback);
	}
	protected function initialize() {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		parent::initialize();
		$zesk->classes->register("zesk\Database_Schema_File");
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
			zesk\Database_Schema::$debug = true;
		}
		$url = null;
		if ($this->has_option("url")) {
			$url = $this->option('url');
			if (!URL::valid($url)) {
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
		
		$database = $application->database_factory($url);
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
