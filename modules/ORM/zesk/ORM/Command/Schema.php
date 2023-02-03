<?php declare(strict_types=1);

/**
 *
 */
namespace zesk\ORM;

use zesk\ArrayTools;
use zesk\Command_Base;
use zesk\Exception;
use zesk\URL;

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
		'check' => 'boolean',
		'url' => 'string',
		'name' => 'string',
		'update' => 'boolean',
		'no-hooks' => 'boolean',
		'*' => 'string',
	];

	protected array $shortcuts = ['schema'];

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
	public function results(): array {
		return $this->results;
	}

	/**
	 */
	protected function synchronize_before(): void {
		if (!$this->optionBool('no-hooks')) {
			$this->_synchronize_suffix('update');
		}
	}

	/**
	 */
	protected function synchronize_after(): void {
		if ($this->optionBool('update')) {
			if (!$this->optionBool('no-hooks')) {
				$this->_synchronize_suffix('updated');
			}
		}
	}

	/**
	 * Add calling callback log
	 *
	 * @param callable $callable
	 */
	public function hook_callback(callable $callable): void {
		$this->debugLog('{class}: Calling {callable}', [
			'class' => __CLASS__,
			'callable' => $this->application->hooks->callable_string($callable),
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
	private function _synchronize_suffix(string $suffix): void {
		$hook_callback = [
			$this,
			'hook_callback',
		];

		$app = $this->application;

		$hook_type = ORMBase::class . "::schema_$suffix";
		$all_hooks = $this->application->hooks->findAll([$hook_type]);

		$all = implode(', ', array_keys($all_hooks));
		$app->logger->notice("Running all $suffix hooks {hooks}", [
			'hooks' => $all ?: '- no hooks found',
		]);
		$this->application->hooks->allCallArguments([$hook_type], [
			$this->application,
		], null, $hook_callback);

		$hook_type = "schema_$suffix";
		$all = $app->modules->listAllHooks($hook_type);
		$hooks_strings = [];
		if (count($all) !== 0) {
			foreach ($all as $hook) {
				$hooks_strings[] = $app->hooks->callable_string($hook);
			}
		}
		$app->logger->notice("Running module $suffix hooks {hooks}", [
			'hooks' => $hooks_strings ? implode(', ', $hooks_strings) : '- no hooks found',
		]);
		$app->modules->allHookArguments($hook_type, [
			$this->application,
		], null, $hook_callback);

		$app_hooks = $app->listHooks($hook_type);
		$app_hooks_strings = '- no hooks found';
		if (count($app_hooks) !== 0) {
			$app_hooks_strings = [];
			foreach ($app_hooks as $hook) {
				$app_hooks_strings[] = $app->hooks->callable_string($hook);
			}
		}
		$app->logger->notice("Running application $suffix hooks {hooks}", [
			'hooks' => $app_hooks_strings,
		]);
		$app->callHookArguments($hook_type, [
			$this->application,
		], null, $hook_callback);
	}

	/**
	 * @return void
	 */
	protected function initialize(): void {
		parent::initialize();
		$this->application->registerClass(Schema_File::class);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Command::run()
	 */
	protected function run(): int {
		$application = $this->application;

		if ($this->optionBool('debug')) {
			Schema::$debug = true;
		}
		$url = '';
		if ($this->hasOption('url')) {
			$url = $this->option('url');
			if (!URL::valid($url)) {
				$this->usage('--url is not a valid URL, e.g. mysql://user:password@host/database');
			}
		} elseif ($this->hasOption('name')) {
			$url = $this->option('name');
		}
		$classes = null;
		if ($this->hasArgument()) {
			$classes = $this->argumentsRemaining();
			$this->verboseLog('Running on classes {classes}', compact('classes'));
		}

		$this->synchronize_before();

		$database = $application->databaseRegistry($url);
		$this->results = $results = $application->ormModule()->schema_synchronize($database, $classes, [
			'skip_others' => true,
			'check' => $this->optionBool('check'),
		]);
		$suffix = ";\n";
		if ($this->optionBool('update')) {
			foreach ($results as $index => $sql) {
				try {
					$result = $database->query($sql);
					$this->results[$index] = [
						'sql' => $sql,
						'result' => $result,
					];
					echo "$sql$suffix";
				} catch (Exception $e) {
					$this->results[$index] = [
						'sql' => $sql,
						'exception' => $e,
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
