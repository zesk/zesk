<?php declare(strict_types=1);

/**
 *
 */
namespace zesk;

/**
 * Job module for running background jobs in a somewhat reliable manner
 *
 * @author kent
 */
class Module_Job extends Module implements Interface_Module_Routes {
	/**
	 *
	 * @var array
	 */
	protected array $model_classes = [
		'zesk\\Job',
	];

	/**
	 * For testing, call this statically from zesk eval, a web request, or a debugger
	 */
	public static function fake_daemon(Application $application): void {
		$quit_after = $application->configuration->getPath(__CLASS__ . '::fake_daemon_quit_after', 5000);
		ini_set('max_execution_time', $quit_after);
		$process = new Process_Mock($application, [
			'quit_after' => $quit_after,
		]);
		Job::execute_jobs($process);
	}

	/**
	 * Run daemon
	 *
	 * @param Interface_Process $process
	 */
	private function run_daemon(Interface_Process $process): void {
		$has_hook = $this->hasHook('wait_for_job');
		$seconds = $this->option('execute_jobs_wait', 10);
		$app = $process->application();
		if (!$has_hook) {
			$app->logger->debug('No hook exists for wait_for_job, sleeping interval is {seconds} seconds', compact('seconds'));
		}

		declare(ticks = 1) {
			while (!$process->done()) {
				Job::execute_jobs($process);
				if ($has_hook) {
					$this->callHookArguments('wait_for_job', [
						$process,
					]);
					$process->sleep(0);
				} else {
					$process->sleep($seconds);
				}
			}
		}
	}

	/**
	 * Daemon hook
	 *
	 * @param Interface_Process $process
	 */
	public static function daemon(Interface_Process $process): void {
		$application = $process->application();
		$module = $application->job_module();
		$module->run_daemon($process);
	}

	/**
	 * Add routes to Router
	 *
	 * {@inheritdoc}
	 *
	 * @see \Interface_Module_Routes::hook_routes()
	 */
	public function hook_routes(Router $router): void {
		$router->addRoute('job/{zesk\\Job job}(/{option action})', [
			'controller' => 'zesk\\Controller_Job',
			'arguments' => [
				1,
			],
			'default action' => 'monitor',
			'module' => 'job',
		]);
		if ($this->application->development() && !$this->optionBool('skip_route_job_execute')) {
			$router->addRoute('job-execute', [
				'method' => [
					__CLASS__,
					'fake_daemon',
				],
				'module' => 'job',
			]);
		}
	}
}
