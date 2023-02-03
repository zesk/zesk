<?php declare(strict_types=1);

/**
 *
 */
namespace zesk\Job;

use zesk\Exception_Unimplemented;
use zesk\PHP;
use zesk\Router;
use zesk\Interface_Process;
use zesk\MockProcess;
use zesk\Module as BaseModule;
use zesk\Interface_Module_Routes;

/**
 * Job module for running background jobs in a somewhat reliable manner
 *
 * @author kent
 */
class Module extends BaseModule implements Interface_Module_Routes {
	/**
	 *
	 * @var array
	 */
	protected array $modelClasses = [
		Job::class,
	];

	/**
	 * For testing, call this statically from zesk eval, a web request, or a debugger
	 */
	public function mockDaemon(): void {
		$application = $this->application;
		$quit_after = $application->configuration->getPath(__CLASS__ . '::fake_daemon_quit_after', 5000);

		try {
			PHP::setFeature(PHP::FEATURE_TIME_LIMIT, $quit_after);
		} catch (Exception_Unimplemented $e) {
			$this->application->logger->debug(PHP::FEATURE_TIME_LIMIT . ' reported as {message}', $e->variables());
		}
		$process = new MockProcess($application, [
			'quit_after' => $quit_after,
		]);
		Job::executeJobs($process);
	}

	/**
	 * Run daemon
	 *
	 * @param Interface_Process $process
	 */
	private function runDaemon(Interface_Process $process): void {
		$has_hook = $this->hasHook('wait_for_job');
		$seconds = $this->option('execute_jobs_wait', 10);
		$app = $process->application();
		if (!$has_hook) {
			$app->logger->debug('No hook exists for wait_for_job, sleeping interval is {seconds} seconds', compact('seconds'));
		}

		declare(ticks = 1) {
			while (!$process->done()) {
				Job::executeJobs($process);
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
		$module = $application->jobModule();
		$module->runDaemon($process);
	}

	/**
	 * Add routes to Router
	 **
	 * @see Interface_Module_Routes::hook_routes()
	 */
	public function hook_routes(Router $router): void {
		$router->addRoute('job/{zesk\\Job job}(/{option action})', [
			'controller' => Controller::class,
			'arguments' => [
				1,
			],
			'module' => $this->codeName(),
		]);
		if ($this->application->development() && !$this->optionBool('skip_route_job_execute')) {
			$router->addRoute('job-execute', [
				'method' => $this->mockDaemon(...),
				'module' => $this->codeName(),
			]);
		}
	}
}
