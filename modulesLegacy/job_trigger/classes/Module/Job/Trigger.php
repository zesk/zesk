<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 * Module to allow jobs to be activated via mechanism which isn't polling the database.
 *
 * Uses a trigger file and web requests instead.
 *
 * @author kent
 */
class Module_Job_Trigger extends Module implements Interface_Module_Routes {
	/**
	 * Add hooks for initialize
	 */
	public function initialize(): void {
		parent::initialize();
		$this->application->configuration->deprecated('Module_Job_Trigger', __CLASS__);
		$this->application->hooks->add('zesk\\Job::start', [
			$this,
			'trigger_send',
		]);
		$this->application->hooks->add('zesk\\Job::execute_interrupt', [
			$this,
			'trigger_send',
		]);
		$this->application->hooks->add('Module_Job::wait_for_job', [
			$this,
			'wait_for_job',
		]);
		// We don't care about completion because all that means is a job is completed, not that new jobs are ready to go
	}

	/**
	 * Get/Set wait max seconds before dinging the database again
	 */
	public function wait_max_seconds() {
		return $this->optionInt('wait_max_seconds', 600);
	}

	/**
	 * Create the directory for the marker file if it does not exist and return the path to the marker file
	 */
	private function marker_file() {
		$path = $this->application->data_path('job_trigger');
		Directory::depend($path);
		return path($path, 'trigger');
	}

	/**
	 * Given a server, provide the URL to contact that server
	 */
	private function trigger_send_pattern(Server $server) {
		$url_pattern = $this->option('trigger_url_pattern', 'http://{ip4_internal}/job_trigger');
		return $server->applyMap($url_pattern);
	}

	private function compute_hash($timestamp) {
		$code = [];
		$code[] = $timestamp;
		$code[] = $this->option('key');
		return md5(implode('|', $code));
	}

	/**
	 * Write the marker to disk
	 */
	public function write_marker(): void {
		file_put_contents($this->marker_file(), microtime(true));
	}

	/**
	 * Add security to a URL if a key exists. For single server-systems, this isn't necessary so only
	 * warn when we're in multi-server environments.
	 *
	 * @param string $url
	 */
	private function add_security($url) {
		if (!$this->hasOption('key')) {
			$this->application->logger->warning('Can not add security to {url} - need to configure {class}::key global', [
				'url' => $url,
				'class' => __CLASS__,
			]);
			return $url;
		}
		$query['timestamp'] = $timestamp = strval(microtime(true));
		$query['hash'] = $this->compute_hash($timestamp);

		return URL::queryAppend($url, $query);
	}

	/**
	 * Check the security tokens passed to us
	 *
	 * @param string $hash
	 * @param string $timestamp
	 */
	public function check_security($hash, $timestamp) {
		if (!$this->hasOption('key')) {
			$this->application->logger->error('Can not check security! need to configure {class}::key global', [
				'class' => __CLASS__,
			]);
			return [
				'status' => false,
				'message' => 'No key configured',
			];
		}
		if (empty($hash) || empty($timestamp)) {
			return [
				'status' => false,
				'message' => 'Require query string parameters hash and timestamp',
			];
		}

		$skew_seconds = abs($timestamp - microtime(true));
		if ($skew_seconds > $server_clock_skew_seconds = $this->option('server_clock_skew_seconds', 30)) {
			return [
				'status' => false,
				'message' => "Clock skew - requests only valid for $server_clock_skew_seconds seconds",
				'seconds' => $skew_seconds,
			];
		}
		$check_hash = $this->compute_hash($timestamp);
		if ($hash !== $check_hash) {
			return [
				'status' => false,
				'message' => 'Hash does not validate',
			];
		}

		return null;
	}

	/**
	 * When we are notified via web, just write the marker file
	 *
	 * @param Application $application
	 */
	public static function web_job_trigger(Application $application, Request $request) {
		$module = $application->job_trigger_module();
		$result = $module->check_security($request->get('hash'), $request->get('timestamp'));
		if (!is_array($result)) {
			/* @var $module Module_Job_Trigger */
			$module->write_marker();
			$result = [
				'status' => true,
			];
		}
		return $result + [
			'elapsed' => microtime(true) - $application->initializationTime(),
			'now' => Timestamp::now()->format(),
		];
	}

	/**
	 * Send a notice to all servers that jobs are waiting
	 */
	public function trigger_send(): void {
		$server = $this->application->objectSingleton('zesk\\Server');
		$servers = $server->querySelect()->addWhere('alive|>=', Timestamp::now()->addUnit(-1, Timestamp::UNIT_MINUTE))->ormIterator();
		foreach ($servers as $other_server) {
			if ($other_server->id() === $server->id()) {
				$this->write_marker();
			} else {
				$url = $this->trigger_send_pattern($other_server);
				if ($url) {
					$this->application->logger->debug('Notifying job trigger for {url}', compact('url'));
					$url = $this->add_security($url);
					$this->application->process->execute('curl --connect-timeout 2 {0} > /dev/null 2>&1 &', $url);
				}
			}
		}
	}

	/**
	 * Wait for the job by seeing if our marker file exists
	 */
	public function wait_for_job(Module_Job $module, Interface_Process $process): void {
		$timer = new Timer();
		$marker = $this->marker_file();
		$this->application->logger->debug('{method} waiting for marker to appear: {marker}', [
			'method' => __METHOD__,
			'marker' => $marker,
		]);
		if (!file_exists($marker)) {
			do {
				$marker = $this->marker_file();
				$process->sleep(0.1); // 0.1 second
				if ($timer->elapsed() > $this->wait_max_seconds()) {
					break;
				}
			} while (!file_exists($marker));
		}
		$this->application->logger->debug('{method} deleting marker {marker}', [
			'method' => __METHOD__,
			'marker' => $marker,
		]);
		if (file_exists($marker)) {
			File::unlink($marker);
		}
	}

	/**
	 * Add routes to do our job
	 *
	 * @param Router $router
	 */
	public function hook_routes(Router $router): void {
		if ($this->hasOption('key')) {
			// Only allowed to receive web requests with some form of shared key security.
			// If you don't configure this works fine on single-server applications
			$router->addRoute('job_trigger', [
				'method' => [
					__CLASS__,
					'web_job_trigger',
				],
				'content type' => Response::CONTENT_TYPE_JSON,
				'arguments' => [
					'{application}',
					'{request}',
				],
			]);
		}
		$router->addRoute('job_trigger/active', [
			'content' => true,
			'content type' => Response::CONTENT_TYPE_JSON,
		]);
	}
}
