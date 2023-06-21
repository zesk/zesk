<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk\Job;

use Throwable;
use zesk\Application;
use zesk\Daemon\Attributes\DaemonMethod;
use zesk\Exception\ClassNotFound;
use zesk\Exception\ConfigurationException;
use zesk\Exception\KeyNotFound;
use zesk\Exception\ParameterException;
use zesk\Exception\ParseException;
use zesk\Exception\Semantics;
use zesk\Exception\Unimplemented;
use zesk\HookMethod;
use zesk\Interface\Module\Routes;
use zesk\Interface\SystemProcess;
use zesk\PHP;
use zesk\Router;
use zesk\MockProcess;
use zesk\Module as BaseModule;

/**
 * Job module for running background jobs in a somewhat reliable manner
 *
 * @author kent
 */
class Module extends BaseModule implements Routes {
	/**
	 * Call a method to wait for a job to be delivered instead of polling the database (default) every n seconds.
	 */
	public const HOOK_WAIT_FOR_JOB = __CLASS__ . '::waitForJob';

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
		} catch (Unimplemented $e) {
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
	 * @param SystemProcess $process
	 * @throws Throwable
	 * @throws ClassNotFound
	 * @throws ConfigurationException
	 * @throws KeyNotFound
	 * @throws ParameterException
	 * @throws ParseException
	 * @throws Semantics
	 */
	private function runDaemon(SystemProcess $process): void {
		$seconds = $this->option('execute_jobs_wait', 10);
		$app = $process->application();
		$hasHooks = $this->hasHooks(self::HOOK_WAIT_FOR_JOB);
		if (!$hasHooks) {
			$app->logger->debug('No hook exists for wait_for_job, sleeping interval is {seconds} seconds', compact('seconds'));
		}

		declare(ticks=1) {
			while (!$process->done()) {
				Job::executeJobs($process);
				if ($hasHooks) {
					$this->invokeHooks(self::HOOK_WAIT_FOR_JOB, [$process, $this]);
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
	 * @param SystemProcess $process
	 * @throws ClassNotFound
	 * @throws ConfigurationException
	 * @throws KeyNotFound
	 * @throws ParameterException
	 * @throws ParseException
	 * @throws Semantics
	 * @throws Throwable
	 * @see self::daemon()
	 */
	#[DaemonMethod]
	public static function daemon(SystemProcess $process): void {
		$application = $process->application();
		$module = $application->jobModule();
		$module->runDaemon($process);
	}

	/**
	 * Add routes to Router
	 **
	 * @see Routes::hook_routes()
	 */
	#[HookMethod(handles: Application::HOOK_ROUTER)]
	public function hook_routes(Router $router): void {
		$router->addRoute('job/{' . Job::class . ' job}(/{option action})', [
			'controller' => Controller::class, 'arguments' => [
				1,
			], 'module' => $this->codeName(),
		]);
		if ($this->application->development() && !$this->optionBool('skip_route_job_execute')) {
			$router->addRoute('job-execute', [
				'method' => $this->mockDaemon(...), 'module' => $this->codeName(),
			]);
		}
	}
}
