<?php
declare(strict_types=1);

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
	protected $skipped = [];

	/**
	 * Our options
	 *
	 * @var array
	 */
	protected array $options = [
		'last_tag' => '-none-',
	];

	/**
	 *
	 * @param Application $application
	 * @param string $path
	 * @param array $options
	 * @return \zesk\Deploy
	 */
	public static function factory(Application $application, string $path, array $options = []) {
		return new Deploy($application, $path, $options);
	}

	/**
	 *
	 * @param Application $application
	 * @param string $path
	 * @param array $options
	 */
	public function __construct(Application $application, string $path, array $options = []) {
		parent::__construct($application, $options);
		$this->path = $path;
		$this->callHook('construct');
	}

	/**
	 *
	 * Run a deployment check, using path for deployment state
	 *
	 * @param string $path
	 *            Path to deployme
	 * @param Settings $settings
	 * @return Deploy
	 */
	public static function settings_maintenance(string $path, Interface_Settings $settings): Deploy {
		$app = $settings->application;
		$setting_name = __CLASS__ . '::state';
		$settings->deprecated('deploy', $setting_name);
		$options = toArray($settings->get($setting_name));

		$deploy = new Deploy($app, $path, $options);
		if ($deploy->failed()) {
			$deploy->reset(true);
		}
		$lock = Lock::instance($app, 'deploy');
		if ($lock->acquire() !== null) {
			$deploy->_maintenance();
			$results = $deploy->options();
			$settings->set($setting_name, $results);
			$settings->flush();
			$lock->release();
		} else {
			$app->logger->warning('Deployment is already running.');
		}
		return $deploy;
	}

	/**
	 *
	 * @param bool $skip
	 * @return self
	 * @throws Exception_Semantics
	 */
	public function reset(bool $skip = false): self {
		if (!$this->failed()) {
			return $this;
		}
		$last_tag = $this->optionPath('last_tag');
		$failed_tag = $this->optionPath('failed_tag.name');
		$args = ['last_tag' => $last_tag, 'failed_tag' => $failed_tag];
		$suffix = '(Last: "{last_tag}" Failed: "{failed_tag}")';
		if (empty($last_tag)) {
			//error("Deploy::reset({skip}) Empty tags found last_tag={last_tag} failed_tag={failed_tag}", compact("last_tag", "failed_tag", "skip"));
			throw new Exception_Semantics("Last tag empty $suffix", $args);
		}
		if (empty($failed_tag)) {
			throw new Exception_Semantics("Failed tag empty $suffix", $args);
		}
		return $this->setOptions([
			'status' => true, 'last_tag' => $skip ? $failed_tag : $last_tag,
		]);
	}

	/**
	 * Did the deploy fail?
	 *
	 * @return boolean
	 */
	public function failed(): bool {
		return !$this->optionBool('status', true);
	}

	/**
	 *
	 * @param string $path
	 * @return ?array
	 */
	private function _parseTag(string $path): ?array {
		if (!is_file($path)) {
			return null;
		}
		$tag = [];
		$parts = pathinfo($path);
		$filename = strtolower($parts['filename']);
		$extension = strtolower($parts['extension']);
		if (!$this->extensionIsHandled($extension)) {
			return null;
		}
		$tag['path'] = $path;
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
	private function extensionIsHandled(string $extension): bool {
		return method_exists($this, "hook_extension_$extension");
	}

	/**
	 *
	 * @return array
	 */
	private function loadTags(): array {
		$last_tag = $this->option('last_tag');

		try {
			$subPaths = Directory::ls($this->path, null, true);
		} catch (Exception_Directory_NotFound $e) {
			return [];
		}
		$tags = [];
		foreach ($subPaths as $subpath) {
			$tag = $this->_parseTag($subpath);
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
	 * @return self
	 */
	protected function _maintenance(): self {
		$logger = $this->application->logger;
		$last_tag = $this->option('last_tag');
		$tags = $this->loadTags();
		$results = [];
		if (count($this->skipped) > 0) {
			$results['skipped'] = $this->skipped;
		}
		$results['new_tags'] = array_keys($tags);
		$start = time();
		$results['started'] = date('Y-m-d H:i:s');
		$results['first_tag'] = $last_tag;
		$this->setOption('applied', null);
		$this->setOption('failed_tag', null);
		$this->setOption('failed_result', null);
		$results['last_tag'] = $last_tag;
		$results['status'] = true;
		if (count($tags) > 0) {
			$logger->notice('Last tag succeeded was {last_tag}', $this->options);
			$logger->notice('Processing tags: {tags}', [
				'tags' => _dump($tags),
			]);
			foreach ($tags as $name => $subpaths) {
				foreach ($subpaths as $subpath => $tag) {
					$extension = $tag['extension'];
					$result = $this->callHookArguments("extension_$extension", [
						$tag,
					], []);
					if (!is_array($result) || !$result['status']) {
						$logger->error('Tag failed: {tag} {message}', $result + [
							'tag' => $tag,
						]);
						$results['failed_tag'] = $tag;
						$results['failed_result'] = $result;
						$results['status'] = false;
						$this->setOptions($results);
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
		$this->setOptions($results);
		return $this;
	}

	/**
	 * Run a deployment script which is a PHP include script
	 *
	 * @param array $tag
	 * @return array
	 * @see self::hook_extension_php
	 */
	protected function hook_extension_inc(array $tag): array {
		return $this->hook_extension_php($tag);
	}

	/**
	 * Run a deployment script which is a PHP include script
	 *
	 * @param array $tag
	 * @return array
	 */
	protected function hook_extension_php(array $tag): array {
		$path = $tag['path'];
		ob_start();
		$status = true;

		try {
			$this->application->logger->notice('Including PHP file {path}', compact('path'));
			$app = $application = $this->application;
			$result = @include $path;
		} catch (\Exception $e) {
			$this->application->hooks->call('exception', $e);
			$status = false;
			$result = null;
		}
		$content = ob_end_clean();
		return [
			'path' => $path, 'type' => 'php', 'status' => $status, 'result' => $result, 'content' => $content,
		];
	}

	/**
	 * Run a deployment script which is a TPL file (include)
	 *
	 * @param array $tag
	 * @return array
	 */
	protected function hook_extension_tpl(array $tag): array {
		$path = $tag['path'];
		$status = true;

		try {
			$this->application->logger->notice('Loading template {path}', compact('path'));
			$content = new Template($this->application, $path);
		} catch (\Exception $e) {
			$this->application->hooks->call('exception', $e);
			$content = null;
			$status = false;
		}
		return [
			'type' => 'tpl', 'content' => $content, 'status' => $status,
		];
	}

	/**
	 * Run a deployment script which is a SQL file
	 *
	 * @param array $tag
	 * @return array
	 */
	protected function hook_extension_sql(array $tag): array {
		$path = $tag['path'];
		$db = $this->application->databaseRegistry();
		$sqlStatements = $db->splitSQLStatements(file_get_contents($path));
		$result = [
			'type' => 'sql',
		];

		while (count($sqlStatements) > 0) {
			$sql = array_shift($sqlStatements);

			try {
				if (!$db->query($sql)) {
					$result['message'] = "SQL Failed: $sql";
					$result['failed_sql'] = $sql;
					$result['unapplied'] = $sqlStatements;
					$result['status'] = false;
					return $result;
				} else {
					$result['applied'][] = $sql;
				}
			} catch (\Exception $e) {
				$this->application->hooks->call('exception', $e);
				$result['message'] = map('Exception {class}: {message}', [
					'class' => $e::class, 'message' => $e->getMessage(),
				]);
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
