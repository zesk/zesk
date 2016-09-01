<?php
class Deploy extends Hookable {
	protected $path = null;
	protected $skipped = array();
	protected $options = array(
		"last_tag" => "-none-"
	);
	/**
	 *
	 * Run a deployment check, using path for deployment state
	 *
	 * @param string $path Path to deployme
	 * @param zesk\Settings $settings
	 * @return NULL|Deploy
	 */
	public static function settings_maintenance($path, zesk\Settings $settings) {
		global $zesk;
		
		$options = to_array($settings->deploy);
		$deploy = new Deploy($path, $options);
		if ($deploy->failed()) {
			$deploy->reset(true);
		}
		if (($lock = Lock::get_lock('deploy')) !== null) {
			$deploy->_maintenance();
			$results = $deploy->option();
			$settings->deploy = $results;
			$settings->flush();
			$lock->release();
		} else {
			$zesk->logger->warning("Deployment is already running.");
		}
		return $deploy;
	}
	public static function factory($path, $options = null) {
		return new Deploy($path, $options);
	}
	public function reset($skip = false) {
		if (!$this->failed()) {
			return $this;
		}
		$last_tag = $this->option_path('last_tag');
		$failed_tag = $this->option_path('failed_tag.name');
		if (empty($last_tag) || empty($failed_tag)) {
			#error("Deploy::reset({skip}) Empty tags found last_tag={last_tag} failed_tag={failed_tag}", compact("last_tag", "failed_tag", "skip"));
			return null;
		}
		return $this->set_option(array(
			'status' => true,
			'last_tag' => $skip ? $failed_tag : $last_tag
		));
	}
	public function failed() {
		return !$this->option_bool('status', true);
	}
	public function __construct($path, $options = null) {
		$this->path = $path;
		parent::__construct($options);
		$this->hook('construct');
	}
	private static function _parse_tag($subpath) {
		$tag = array();
		$filename = $extension = null;
		extract(pathinfo($subpath), EXTR_IF_EXISTS);
		$filename = strtolower($filename);
		$extension = strtolower($extension);
		if (!is_file($subpath)) {
			return null;
		}
		if (!in_array($extension, array(
			'tpl',
			'sql',
			'inc'
		))) {
			return null;
		}
		$tag['path'] = $subpath;
		$tag['extension'] = $extension;
		$tag['name'] = $filename;
		return $tag;
	}
	private function load_tags() {
		$last_tag = $this->option('last_tag');
		try {
			$subpaths = dir::ls($this->path, null, true);
		} catch (Exception_Directory_NotFound $e) {
			return array();
		}
		$tags = $result = array();
		foreach ($subpaths as $subpath) {
			$tag = self::_parse_tag($subpath);
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
	protected function _maintenance() {
		global $zesk;
		/* @var $zesk zesk\Kernel */
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
			$zesk->logger->notice('Last tag succeeded was {last_tag}', $this->options);
			$zesk->logger->notice("Processing tags: {tags}", array(
				"tags" => _dump($tags)
			));
			foreach ($tags as $name => $subpaths) {
				foreach ($subpaths as $subpath => $tag) {
					$extension = $tag['extension'];
					$result = $this->hook_array("extension_$extension", array(
						$tag
					), array());
					if (!is_array($result) || !avalue($result, 'status')) {
						$zesk->logger->error("Tag failed: {tag} {message}", $result + array(
							"tag" => $tag
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
	protected function hook_extension_inc(array $tag) {
		$path = $tag['path'];
		ob_start();
		$status = true;
		try {
			log::notice("Including PHP file {path}", compact("path"));
			$result = @include $path;
		} catch (Exception $e) {
			global $zesk;
			$zesk->hooks->call("exception", $e);
			$status = false;
			$result = null;
		}
		$content = ob_end_clean();
		return array(
			'type' => 'inc',
			'status' => $status,
			'result' => $result,
			'content' => $content
		);
	}
	protected function hook_extension_tpl(array $tag) {
		global $zesk;
		$path = $tag['path'];
		$status = true;
		try {
			$zesk->logger->notice("Loading template {path}", compact("path"));
			$content = Template::instance($path);
		} catch (Exception $e) {
			$zesk->hooks->call("exception", $e);
			$status = false;
		}
		return array(
			'type' => 'tpl',
			'content' => $content,
			'status' => $status
		);
	}
	protected function hook_extension_sql(array $tag) {
		$path = $tag['path'];
		$db = app()->database_factory();
		$sqls = $db->split_sql_commands(file_get_contents($path));
		$result = array(
			'type' => 'sql'
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
			} catch (Exception $e) {
				global $zesk;
				$zesk->hooks->call("exception", $e);
				$result['message'] = map("Exception {class}: {message}", array(
					"class" => get_class($e),
					"message" => $e->getMessage()
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