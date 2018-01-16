<?php

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
	protected $model_classes = array(
		'zesk\\Job'
	);

	/**
	 * For testing, call this statically from zesk eval, a web request, or a debugger
	 */
	public static function fake_daemon(Application $application) {
		$zesk = $application->zesk;
		$quit_after = $zesk->configuration->path_get(__CLASS__ . "::fake_daemon_quit_after", 5000);
		ini_set('max_execution_time', $quit_after);
		$process = new Process_Mock($application, array(
			"quit_after" => $quit_after
		));
		Job::execute_jobs($process);
	}

	/**
	 * Run daemon
	 *
	 * @param Interface_Process $process
	 */
	private function run_daemon(Interface_Process $process) {
		$has_hook = $this->has_hook("wait_for_job");
		$seconds = $this->option("execute_jobs_wait", 10);
		$app = $process->application();
		if (!$has_hook) {
			$app->logger->debug("No hook exists for wait_for_job, sleeping interval is {seconds} seconds", compact("seconds"));
		}
		declare(ticks = 1) {
			while (!$process->done()) {
				Job::execute_jobs($process);
				if ($has_hook) {
					$this->call_hook_arguments("wait_for_job", array(
						$process
					));
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
	public static function daemon(Interface_Process $process) {
		$application = $process->application();
		$module = $application->modules->object("job");
		$module->run_daemon($process);
	}

	/**
	 * Add routes to Router
	 *
	 * {@inheritdoc}
	 *
	 * @see \Interface_Module_Routes::hook_routes()
	 */
	public function hook_routes(Router $router) {
		$router->add_route("job/{zesk\\Job job}(/{option action})", array(
			"controller" => "zesk\\Controller_Job",
			"arguments" => array(
				1
			),
			'default action' => 'monitor',
			'module' => 'job'
		));
		if ($this->application->development() && !$this->option_bool("skip_route_job_execute")) {
			$router->add_route("job-execute", array(
				"method" => array(
					__CLASS__,
					"fake_daemon"
				),
				'module' => 'job'
			));
		}
	}
}
