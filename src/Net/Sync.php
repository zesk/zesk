<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 */

namespace zesk\Net;

use zesk\Application;
use zesk\Directory;
use zesk\Exception\DirectoryCreate;
use zesk\Exception\DirectoryNotFound;
use zesk\Exception\DirectoryPermission;
use zesk\Exception\DomainLookupFailed;
use zesk\Exception\FileNotFound;
use zesk\Exception\FilePermission;
use zesk\Exception\KeyNotFound;
use zesk\Exception\ParameterException;
use zesk\Exception\UnsupportedException;
use zesk\File;
use zesk\Net\HTTP\Client as Net_HTTP_Client;
use zesk\Net\HTTP\Client\Exception;
use zesk\Options;
use zesk\StringTools;
use zesk\Timestamp;
use zesk\Types;

/**
 *
 * @author kent
 *
 */
class Sync extends Options
{
	/**
	 * The path to store temporary files
	 */
	public const OPTION_TEMPORARY_PATH = 'temporaryPath';

	/**
	 * The number of seconds for files to be valid after downloaded.
	 */
	public const OPTION_TIME_TO_LIVE_SECONDS = 'ttl';

	/**
	 * Connection and download requests time out after this time span
	 */
	public const OPTION_TIMEOUT_SECONDS = 'timeout';

	/**
	 * Use this user agent when requesting files from the remote system
	 */
	public const OPTION_USER_AGENT = 'userAgent';

	/**
	 * Default timeout in seconds (2 minutes))
	 */
	public const DEFAULT_TIMEOUT_SECONDS = 2 * 60;

	/**
	 * Default time to live - once a day. Be considerate of remote systems.
	 */
	public const DEFAULT_TIME_TO_LIVE_SECONDS = 86400; // Once a day

	/**
	 *
	 * @var FileSystem
	 */
	protected FileSystem $src;

	/**
	 *
	 * @var FileSystem
	 */
	protected FileSystem $dst;

	/*
	 * Synchronization stats
	 */
	private array $stats = [];

	/**
	 * @var array
	 */
	private array $walkFilter = [true];

	/**
	 * @var array
	 */
	private array $fileFilter = [true];

	/**
	 * @var array
	 */
	private array $directoryFilter = [true];

	/**
	 * Sync a local file with a destination file, optionally checking checksums
	 *
	 * @param Application $application The current application context.
	 * @param string $url
	 *                                 URL of remote file to download
	 * @param string $path
	 *                                 Local file system path of destination, requires a file name to use timestamp
	 *                                 checking on local file
	 * @param array $options
	 *                                 Options to configure how this works:
	 *                                 - self::OPTION_TIME_TO_LIVE_SECONDS - Check remote URL for changes every n seconds (defaults to 1 day)
	 *                                 - self::OPTION_TIMEOUT_SECONDS - Seconds to timeout remote retrieval
	 *                                 - self::OPTION_USER_AGENT - User agent to use
	 *
	 * @return bool|null true if file has changed, false if not, null if time-to-live has not expired
	 * @throws DirectoryCreate
	 * @throws DirectoryNotFound
	 * @throws DirectoryPermission
	 * @throws DomainLookupFailed
	 * @throws FilePermission
	 * @throws Exception
	 * @throws ParameterException
	 * @throws UnsupportedException
	 */
	public static function urlToFile(Application $application, string $url, string $path, array $options = []): ?bool
	{
		$options = $options + [self::OPTION_TIMEOUT_SECONDS => self::DEFAULT_TIMEOUT_SECONDS];
		$path = File::validateWritable($path);
		$time_to_live = $options[self::OPTION_TIME_TO_LIVE_SECONDS] ?? self::DEFAULT_TIME_TO_LIVE_SECONDS; // Once a day
		if (is_file($path) && $time_to_live > 0) {
			$mtime = filemtime($path);
			if (time() - $mtime < $time_to_live) {
				return null;
			}
		}
		[
			$temp,
			$server_name,
		] = self::fetchURL($application, $url, $options);
		$full_path = str_ends_with($path, '/') ? Directory::path($path, $server_name) : $path;
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
	 * @param Application $application
	 * @param string $url
	 * @param array $options
	 * @return array
	 * @throws DirectoryCreate
	 * @throws DirectoryNotFound
	 * @throws DirectoryPermission
	 * @throws FilePermission
	 * @throws HTTP\Client\Exception
	 * @throws DomainLookupFailed
	 * @throws ParameterException
	 * @throws UnsupportedException
	 */
	private static function fetchURL(Application $application, string $url, array $options = []): array
	{
		$milliseconds = Types::toInteger($options['timeout'] ?? null);
		$user_agent = $options[self::OPTION_USER_AGENT] ?? null;

		$client = new Net_HTTP_Client($application, $url);
		if ($milliseconds) {
			$client->setTimeout($milliseconds);
		}
		$temporary_path = $options['temporary_path'] ?? $application->paths->temporary();
		$temp_file_name = File::temporary($temporary_path);
		$client->setFollowLocation(true);
		if ($user_agent) {
			$client->setUserAgent($user_agent);
		}
		$client->setDestination($temp_file_name);

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
	 * @param Application $application
	 * @return array Transfer statistics
	 * @return int[]
	 * @throws DirectoryCreate
	 * @throws DirectoryNotFound
	 * @throws DirectoryPermission
	 * @throws Exception
	 * @throws FileNotFound
	 * @throws FilePermission
	 * @throws KeyNotFound
	 */
	public static function urls(Application $application, string $source_url, string $destination_url, array $options = []): array
	{
		$factory = $application->modules->load('Net')->clientFactory(...);
		$src_client = $factory($source_url, $options);
		$dst_client = $factory($destination_url, $options);
		$sync = new self($src_client, $dst_client, $options);
		return $sync->go();
	}

	/**
	 * Create sync object
	 *
	 * @param FileSystem $source
	 * @param FileSystem $destination
	 * @param array $options
	 *            Settings for the sync
	 */
	public function __construct(FileSystem $source, FileSystem $destination, array $options = [])
	{
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
	/**
	 * @param string|bool $pattern
	 * @param string $default
	 * @return string|bool
	 */
	private function setFilter(string|bool $pattern, string $default): string|bool
	{
		if ($pattern === '') {
			return $default;
		}
		$srcVar = Types::toBool($pattern, null);
		if (is_bool($srcVar)) {
			return $srcVar;
		}
		if (str_starts_with($pattern, '/')) {
			return $pattern;
		}
		return '/' . preg_quote($pattern) . '/';
	}

	public function setWalkFilter(array $filter): static
	{
		$this->walkFilter = $filter;
		return $this;
	}

	public function setFileFilter(array $filter): static
	{
		$this->fileFilter = $filter;
		return $this;
	}

	public function setDirectoryFilter(array $filter): static
	{
		$this->directoryFilter = $filter;
		return $this;
	}

	/**
	 * Given a filename, should it be included in this sync?
	 *
	 * @param string $filename
	 * @param array $filter
	 * @return boolean
	 */
	private function _allow(string $filename, array $filter): bool
	{
		return StringTools::filter($filename, $filter);
	}

	/**
	 * Allow this file to be synced?
	 *
	 * @param string $filename
	 * @return boolean
	 */
	private function canSyncFile(string $filename): bool
	{
		return $this->_allow($filename, $this->fileFilter);
	}

	/**
	 * Allow this directory to be synced?
	 *
	 * @param string $filename
	 * @return boolean
	 */
	private function isDirectoryIncluded(string $filename): bool
	{
		return $this->_allow($filename, $this->directoryFilter);
	}

	/**
	 * Allow this directory to be synced?
	 *
	 * @param string $filename
	 * @return boolean
	 */
	private function canWalk(string $filename): bool
	{
		return $this->_allow($filename, $this->walkFilter);
	}

	/**
	 * Should this file be synced based on entry values?
	 *
	 * @param array $src_entry
	 * @param array $dst_entry
	 * @param boolean $check_mtime
	 *            If mod time should be checked.
	 * @return boolean
	 */
	private function _should_sync(array $src_entry, array $dst_entry, bool $check_mtime): bool
	{
		if ($src_entry['size'] !== $dst_entry['size'] ?? -1) {
			return true;
		}
		if (($dst_entry['type'] ?? null) === null) {
			return true;
		}
		if (!$check_mtime) {
			return false;
		}
		$mtime_src = $src_entry['mtime'];
		$mtime_src_granularity = $src_entry['mtime_granularity'] ?? 'second';
		$mtime_dst = $dst_entry['mtime'] ?? null;
		if (!$mtime_dst) {
			return true;
		}
		$mtime_dst_granularity = $dst_entry['mtime_granularity'] ?? 'second';
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
	 * @return int[]
	 * @throws DirectoryNotFound
	 * @throws DirectoryCreate
	 * @throws DirectoryNotFound
	 * @throws Exception
	 * @throws FilePermission
	 * @throws DirectoryPermission
	 * @throws FileNotFound
	 * @throws KeyNotFound
	 */
	public function go(): array
	{
		$logger = $this->src->application();

		$src = $this->src;
		$dst = $this->dst;

		$src_url = $src->url();
		$src_root = Directory::addSlash($src->urlComponent('path'));
		$src_root_length = strlen($src_root);

		$dst_url = $dst->url();
		$dst_root = Directory::addSlash($dst->urlComponent('path'));

		if (!$src->cd($src_root)) {
			throw new DirectoryNotFound("Source \"$src_url\" directory \"$src_root\" not found");
		}
		if (!$dst->cd($dst_root)) {
			if (!$dst->mkdir($dst_root)) {
				throw new DirectoryCreate($dst_root, $dst_url);
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

				$full_src_path = Directory::path($src_path, $name);
				$rel_path = substr($full_src_path, $src_root_length);
				if (!$this->isDirectoryIncluded($rel_path)) {
					$logger->debug("Skipping directory \"$rel_path\"");
					$stats['ignore']++;
					continue;
				}
				$full_dst_path = Directory::path($dst_root, $rel_path);

				$logger->debug("sync $full_src_path");

				$dst_entry = $dst->stat($full_dst_path);
				$dst_type = $dst_entry['type'] ?? null;

				if ($type === 'dir') {
					if ($dst_type === null) {
						if (!$dst->mkdir($full_dst_path)) {
							throw new DirectoryCreate($full_dst_path, $dst_url);
						} else {
							$stats['mkdir']++;
							$logger->debug("mkdir $full_dst_path at $dst_url");
						}
					}
					if ($this->canWalk($rel_path)) {
						$stats['dirs']++;
						$dir_queue[] = $full_src_path;
					} else {
						$stats['ignore']++;
						$logger->debug("Skipping walking directory \"$rel_path\"");
					}
				} elseif ($type === 'file') {
					if ($this->canSyncFile($rel_path)) {
						$stats['files']++;
						if ($this->_should_sync($src_entry, $dst_entry, $check_mtime)) {
							$temp = File::temporary(dirname($full_dst_path));
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
						$stats['ignore']++;
						$logger->debug("Skipping syncing file \"$rel_path\"");
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
	 * @return array
	 */
	public function stats(): array
	{
		return $this->stats;
	}
}
