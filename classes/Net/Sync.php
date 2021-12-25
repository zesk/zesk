<?php declare(strict_types=1);

/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Net_Sync extends Options {
	/**
	 *
	 * @var Net_FileSystem
	 */
	protected $src;

	/**
	 *
	 * @var Net_FileSystem
	 */
	protected $dst;

	/*
	 * Synchronization stats
	 */
	private $stats = [];

	/**
	 * Sync a local file with a destination file, optionally checking checksums
	 *
	 * @param Application $application The current application context.
	 * @param string $url
	 *        	URL of remote file to download
	 * @param string $path
	 *        	Local file system path of destination, requires a file name to use timestamp
	 *        	checking on local file
	 * @param array $options
	 *        	Options to configure how this works:
	 *        	- time_to_live - Check remote URL for changes every n seconds (defaults to 1 day)
	 *        	- timeout - Seconds to timeout remote retrieval
	 *        	- user_agent - User agent to use
	 *
	 * @return boolean true if file has changed, false if not, null if time-to-live has not expired
	 */
	public static function url_to_file(Application $application, $url, $path, array $options = []) {
		if (!isset($options['timeout'])) {
			$options['timeout'] = 2 * 60 * 1000;
		}
		$path = File::validate_writable($path);
		$time_to_live = avalue($options, 'time_to_live', 86400); // Once a day
		if (is_file($path) && $time_to_live > 0) {
			$mtime = filemtime($path);
			if (time() - $mtime < $time_to_live) {
				return null;
			}
		}
		[$temp, $server_name] = self::_fetch_url($application, $url, $options);
		$full_path = ends($path, "/") ? path($path, $server_name) : $path;
		if (!is_file($full_path) || (md5_file($temp) !== md5_file($full_path))) {
			if (file_exists($full_path)) {
				unlink($full_path);
			}
			rename($temp, $full_path);
			return true;
		} else {
			unlink($temp);
			touch($path, time(), time());
			return false;
		}
	}

	/**
	 *
	 * @param string $url
	 * @param array $options
	 * @return string[]|string[]
	 */
	private static function _fetch_url(Application $application, $url, array $options = []) {
		$milliseconds = to_integer(avalue($options, 'timeout'));
		$user_agent = avalue($options, 'user_agent');

		$client = new Net_HTTP_Client($application, $url);
		if ($milliseconds) {
			$client->timeout($milliseconds);
		}
		$temporary_path = avalue($options, "temporary_path", $application->paths->temporary());
		$temp_file_name = File::temporary($temporary_path);
		$client->follow_location(true);
		if ($user_agent) {
			$client->user_agent($user_agent);
		}
		$client->destination($temp_file_name);

		$result = $client->go();

		$filename = $client->filename();
		return [
			$temp_file_name,
			$filename,
		];
	}

	/**
	 * Utility function to sync two URLs
	 *
	 * @param string $source_url
	 * @param string $destination_url
	 * @param array $options
	 * @return array Transfer statistics
	 */
	public static function urls(Application $application, $source_url, $destination_url, array $options = []) {
		$src_client = Net_Client::factory($application, $source_url, $options);
		$dst_client = Net_Client::factory($application, $destination_url, $options);
		$sync = new Net_Sync($src_client, $dst_client, $options);
		return $sync->go();
	}

	/**
	 * Create sync object
	 *
	 * @param Net_FileSystem $source
	 * @param Net_FileSystem $destination
	 * @param array $options
	 *        	Settings for the sync
	 */
	public function __construct(Net_FileSystem $source, Net_FileSystem $destination, array $options = []) {
		parent::__construct($options);
		$this->src = $source;
		$this->dst = $destination;
	}

	/**
	 * Internal function to manage filter values properly.
	 * If it's a boolean (include all, exclude all), store it.
	 * If it's a pattern, store that. If not, then quote it as a matching pattern.
	 *
	 * @param mixed $pattern
	 * @return mixed
	 */
	private function _set_filter($pattern, $default) {
		if ($pattern === null) {
			return $default;
		}
		$srcVar = to_bool($pattern, null);
		if (is_bool($pattern)) {
			return $pattern;
		}
		if (substr($pattern, 0, 1) == "/") {
			return $pattern;
		}
		return "/" . preg_quote($pattern) . "/";
	}

	public function file_filter($include = null, $exclude = null) {
		$this->option('file_include', $this->_set_filter($include, true));
		$this->option('file_exclude', $this->_set_filter($exclude, false));
		return $this;
	}

	public function directory_filter($include = null, $exclude = null) {
		$this->option('dir_include', $this->_set_filter($include, true));
		$this->option('dir_exclude', $this->_set_filter($exclude, false));
		return $this;
	}

	/**
	 * Given a filename, should it be included in this sync?
	 *
	 * @param string $filename
	 * @param mixed $include
	 * @param mixed $exclude
	 * @return boolean
	 */
	private function _allow($filename, $include, $exclude) {
		if ($include === false) {
			return false;
		}
		if (is_string($include) && !preg_match($include, $filename)) {
			return false;
		}
		if ($exclude === true) {
			return false;
		}
		if ($exclude === false) {
			return true;
		}
		if (is_string($exclude) && preg_match($exclude, $filename)) {
			return false;
		}
		return true;
	}

	/**
	 * Allow this file to be synced?
	 *
	 * @param string $filename
	 * @return boolean
	 */
	private function _file_allow($filename) {
		return $this->_allow($filename, $this->file_include, $this->file_exclude);
	}

	/**
	 * Allow this directory to be synced?
	 *
	 * @param string $filename
	 * @return boolean
	 */
	private function _dir_allow($filename) {
		return $this->_allow($filename, $this->dir_include, $this->dir_exclude);
	}

	/**
	 * Should this file be synced based on entry values?
	 *
	 * @param array $src_entry
	 * @param array $dst_entry
	 * @param boolean $check_mtime
	 *        	If mod time should be checked.
	 * @return boolean
	 */
	private function _should_sync(array $src_entry, array $dst_entry, $check_mtime) {
		if ($src_entry['size'] !== avalue($dst_entry, 'size', -1)) {
			return true;
		}
		if (avalue($dst_entry, 'type') === null) {
			return true;
		}
		if (!$check_mtime) {
			return false;
		}
		$mtime_src = $src_entry['mtime'];
		$mtime_src_granularity = avalue($src_entry, 'mtime_granularity', 'second');
		$mtime_dst = avalue($dst_entry, 'mtime');
		if (!$mtime_dst) {
			return true;
		}
		$mtime_dst_granularity = avalue($dst_entry, 'mtime_granularity', 'second');
		/* @var $mtime_src Timestamp */
		/* @var $mtime_dst Timestamp */
		if ($mtime_src->difference($mtime_dst, $mtime_src_granularity) === 0 || $mtime_src->difference($mtime_dst, $mtime_dst_granularity) === 0) {
			return false;
		}
		return true;
	}

	/**
	 * Synchronize two URLs, recursively.
	 *
	 * @throws Exception_Directory_NotFound
	 * @throws Exception_Directory_Create
	 * @throws Exception
	 */
	public function go() {
		$logger = $this->src->application()->logger;

		$src = $this->src;
		$dst = $this->dst;

		$src_url = $src->url();
		$src_root = Directory::add_slash($src->url('path'));
		$src_root_length = strlen($src_root);

		$dst_url = $dst->url();
		$dst_root = Directory::add_slash($dst->url('path'));

		if (!$src->cd($src_root)) {
			throw new Exception_Directory_NotFound("Source \"$src_url\" directory \"$src_root\" not found");
		}
		if (!$dst->cd($dst_root)) {
			if (!$dst->mkdir($dst_root)) {
				throw new Exception_Directory_Create($dst_root, $dst_url);
			}
		}
		$dir_queue = [
			$src_root,
		];
		$stats = [
			'dirs' => 0,
			'mkdir' => 0,
			'files' => 0,
			'copy' => 0,
			'skip' => 0,
			'total' => 0,
		];
		$check_mtime = $dst->has_feature('mtime');
		while (count($dir_queue) > 0) {
			$src_path = array_shift($dir_queue);
			$stats['dirs']++;
			$src_entries = $src->ls($src_path);
			foreach ($src_entries as $src_entry) {
				$stats['total']++;
				$name = $src_entry['name'];
				$type = $src_entry['type'];

				$full_src_path = path($src_path, $name);
				$rel_path = substr($full_src_path, $src_root_length);
				$full_dst_path = path($dst_root, $rel_path);

				$logger->debug("sync $full_src_path");

				$dst_entry = $dst->stat($full_dst_path);
				$dst_type = avalue($dst_entry, 'type');

				if ($type === 'dir') {
					$stats['dirs']++;
					if ($dst_type === null) {
						if (!$dst->mkdir($full_dst_path)) {
							throw new Exception_Directory_Create($full_dst_path, $dst_url);
						} else {
							$stats['mkdir']++;
							$logger->debug("mkdir $full_dst_path at $dst_url");
						}
					}
					if ($this->_dir_allow($full_src_path)) {
						$dir_queue[] = $full_src_path;
					} else {
						$stats['skip']++;
					}
				} elseif ($type === 'file') {
					$stats['files']++;
					if ($this->_should_sync($src_entry, $dst_entry, $check_mtime)) {
						$temp = File::temporary(dirname($dst_entry));
						$logger->debug("Temporary file: $temp");

						try {
							$src->download($full_src_path, $temp);
							$dst->upload($temp, $full_dst_path, true);
							if ($check_mtime) {
								$dst->mtime($full_dst_path, $src_entry['mtime']);
							}
							$stats['copy']++;
						} catch (Exception $e) {
							if (is_file($temp)) {
								unlink($temp);
							}

							throw $e;
						}
						if (is_file($temp)) {
							unlink($temp);
						}
					} else {
						$stats['skip']++;
					}
				} else {
					$logger->debug("Unhandled type: $type $full_dst_path");
				}
			}
		}
		$this->stats = $stats;
		return $this->stats;
	}

	/**
	 * Retrieve the most recent synchronization stats
	 *
	 * @return multitype:
	 */
	public function stats() {
		return $this->stats;
	}
}
