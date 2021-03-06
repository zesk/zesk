<?php

/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Deploy extends Hookable {
	/**
	 *
	 * @var string
	 */
	protected $path = null;

	/**
	 *
	 * @var array
	 */
	protected $skipped = array();

	/**
	 * Our options
	 *
	 * @var array
	 */
	protected $options = array(
		"last_tag" => "-none-",
	);

	/**
	 *
	 * @param Application $application
	 * @param string $path
	 * @param array $options
	 * @return \zesk\Deploy
	 */
	public static function factory(Application $application, $path, $options = null) {
		return new Deploy($application, $path, $options);
	}

	/**
	 *
	 * @param Application $application
	 * @param unknown $path
	 * @param unknown $options
	 */
	public function __construct(Application $application, $path, array $options = array()) {
		parent::__construct($application, $options);
		$this->path = $path;
		$this->call_hook('construct');
	}

	/**
	 *
	 * Run a deployment check, using path for deployment state
	 *
	 * @param string $path
	 *        	Path to deployme
	 * @param Settings $settings
	 * @return NULL|Deploy
	 */
	public static function settings_maintenance($path, Settings $settings) {
		$app = $settings->application;
		$setting_name = __CLASS__ . "::state";
		$settings->deprecated("deploy", $setting_name);
		$options = to_array($settings->get($setting_name));

		$deploy = new Deploy($app, $path, $options);
		if ($deploy->failed()) {
			$deploy->reset(true);
		}
		$lock = Lock::instance($app, 'deploy');
		if ($lock->acquire() !== null) {
			$deploy->_maintenance();
			$results = $deploy->option();
			$settings->set($setting_name, $results);
			$settings->flush();
			$lock->release();
		} else {
			$app->logger->warning("Deployment is already running.");
		}
		return $deploy;
	}

	/**
	 *
	 * @param string $skip
	 * @return \zesk\Deploy|NULL|void
	 */
	public function reset($skip = false) {
		if (!$this->failed()) {
			return $this;
		}
		$last_tag = $this->option_path('last_tag');
		$failed_tag = $this->option_path('failed_tag.name');
		if (empty($last_tag) || empty($failed_tag)) {
			//error("Deploy::reset({skip}) Empty tags found last_tag={last_tag} failed_tag={failed_tag}", compact("last_tag", "failed_tag", "skip"));
			return null;
		}
		return $this->set_option(array(
			'status' => true,
			'last_tag' => $skip ? $failed_tag : $last_tag,
		));
	}

	/**
	 * Did the deploy fail?
	 *
	 * @return boolean
	 */
	public function failed() {
		return !$this->option_bool('status', true);
	}

	/**
	 *
	 * @param unknown $subpath
	 * @return NULL|unknown[]|string[]
	 */
	private function _parse_tag($subpath) {
		$tag = array();
		$filename = $extension = null;
		extract(pathinfo($subpath), EXTR_IF_EXISTS);
		$filename = strtolower($filename);
		$extension = strtolower($extension);
		if (!is_file($subpath)) {
			return null;
		}
		if (!$this->extension_is_handled($extension)) {
			return null;
		}
		$tag['path'] = $subpath;
		$tag['extension'] = $extension;
		$tag['name'] = $filename;
		return $tag;
	}

	/**
	 * Can we handle a file extension in the deployment directory
	 *
	 * @param string $extension
	 * @return boolean
	 */
	private function extension_is_handled($extension) {
		return method_exists($this, "hook_extension_$extension");
	}

	/**
	 *
	 * @return array
	 */
	private function load_tags() {
		$last_tag = $this->option('last_tag');

		try {
			$subpaths = Directory::ls($this->path, null, true);
		} catch (Exception_Directory_NotFound $e) {
			return array();
		}
		$tags = $result = array();
		foreach ($subpaths as $subpath) {
			$tag = $this->_parse_tag($subpath);
			if ($tag === null) {
				$this->skipped[] = $subpath;

				continue;
			}
			$name = $tag['name'];
			if ($last_tag !== null && strcasecmp($name, $last_tag) <= 0) {
				continue;
			}
			$tags[$name][$subpath] = $tag;
		}
		ksort($tags);
		return $tags;
	}

	/**
	 *
	 * @return self|array
	 */
	protected function _maintenance() {
		$logger = $this->application->logger;
		$last_tag = $this->option('last_tag');
		$tags = $this->load_tags();
		$results = array();
		if (count($this->skipped) > 0) {
			$results['skipped'] = $this->skipped;
		}
		$results['new_tags'] = array_keys($tags);
		$start = time();
		$results['started'] = date('Y-m-d H:i:s');
		$results['first_tag'] = $last_tag;
		$this->set_option('applied', null);
		$this->set_option('failed_tag', null);
		$this->set_option('failed_result', null);
		$results['last_tag'] = $last_tag;
		$results['status'] = true;
		if (count($tags) > 0) {
			$logger->notice('Last tag succeeded was {last_tag}', $this->options);
			$logger->notice("Processing tags: {tags}", array(
				"tags" => _dump($tags),
			));
			foreach ($tags as $name => $subpaths) {
				foreach ($subpaths as $subpath => $tag) {
					$extension = $tag['extension'];
					$result = $this->call_hook_arguments("extension_$extension", array(
						$tag,
					), array());
					if (!is_array($result) || !$result['status']) {
						$logger->error("Tag failed: {tag} {message}", $result + array(
							"tag" => $tag,
						));
						$results['failed_tag'] = $tag;
						$results['failed_result'] = $result;
						$results['status'] = false;
						$this->set_option($results);
						return $results;
					} else {
						$results['applied'][$subpath] = $result;
						$results['last_tag'] = $name;
					}
				}
			}
		}
		$results['ended'] = date('Y-m-d H:i:s');
		$results['duration'] = time() - $start;
		$this->set_option($results);
		return $this;
	}

	/**
	 * Run a deployment script which is a PHP include script
	 *
	 * @see self::hook_extension_php
	 * @param array $tag
	 * @return array
	 */
	protected function hook_extension_inc(array $tag) {
		return $this->hook_extension_php($tag);
	}

	/**
	 * Run a deployment script which is a PHP include script
	 *
	 * @param array $tag
	 * @return array
	 */
	protected function hook_extension_php(array $tag) {
		$path = $tag['path'];
		ob_start();
		$status = true;

		try {
			$this->application->logger->notice("Including PHP file {path}", compact("path"));
			$app = $application = $this->application;
			$result = @include $path;
		} catch (\Exception $e) {
			$this->application->hooks->call("exception", $e);
			$status = false;
			$result = null;
		}
		$content = ob_end_clean();
		return array(
			'path' => $path,
			'type' => 'php',
			'status' => $status,
			'result' => $result,
			'content' => $content,
		);
	}

	/**
	 * Run a deployment script which is a TPL file (include)
	 *
	 * @param array $tag
	 * @return array
	 */
	protected function hook_extension_tpl(array $tag) {
		$path = $tag['path'];
		$status = true;

		try {
			$this->application->logger->notice("Loading template {path}", compact("path"));
			$content = new Template($this->application, $path);
		} catch (\Exception $e) {
			$this->application->hooks->call("exception", $e);
			$status = false;
		}
		return array(
			'type' => 'tpl',
			'content' => $content,
			'status' => $status,
		);
	}

	/**
	 * Run a deployment script which is a SQL file
	 *
	 * @param array $tag
	 * @return array
	 */
	protected function hook_extension_sql(array $tag) {
		$path = $tag['path'];
		$db = $this->application->database_registry();
		$sqls = $db->split_sql_commands(file_get_contents($path));
		$result = array(
			'type' => 'sql',
		);

		while (count($sqls) > 0) {
			$sql = array_shift($sqls);

			try {
				if (!$db->query($sql)) {
					$result['message'] = "SQL Failed: $sql";
					$result['failed_sql'] = $sql;
					$result['unapplied'] = $sqls;
					$result['status'] = false;
					return $result;
				} else {
					$result['applied'][] = $sql;
				}
			} catch (\Exception $e) {
				$this->application->hooks->call("exception", $e);
				$result['message'] = map("Exception {class}: {message}", array(
					"class" => get_class($e),
					"message" => $e->getMessage(),
				));
				$result['exception'] = $e;
				$result['exception_sql'] = $sql;
				$result['status'] = false;
				return $result;
			}
		}
		$result['status'] = true;
		return $result;
	}
}
