<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage subversion
 * @author kent
 * @copyright &copy; 2017 Market Acumen, Inc.
 */
namespace zesk\Subversion;

use zesk\StringTools;
use zesk\ArrayTools;
use zesk\Directory;
use zesk\URL;
use zesk\Exception_Semantics;

/**
 * Subversion repository interface
 * @see Repository
 * @see Repository_Command
 * @author kent
 */
class Repository extends \zesk\Repository_Command {
	/**
	 * Used in validate function
	 *
	 * @var string
	 */
	protected $dot_directory = ".svn";

	/**
	 *
	 * @var string
	 */
	protected $code = "svn";

	/**
	 *
	 * @var string
	 */
	protected $executable = "svn";

	/**
	 * Non-interactive. This is updated during initialize to include the --config-dir directive.
	 *
	 * @var string
	 */
	protected $arguments = " --non-interactive";

	/**
	 * Map from XML status to internal status
	 *
	 * @var array
	 */
	private static $svn_status_map = [
		"added" => self::STATUS_ADDED,
		"modified" => self::STATUS_MODIFIED,
		"missing" => self::STATUS_MISSING,
		"unversioned" => self::STATUS_UNVERSIONED,
	];

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Repository_Command::initialize()
	 */
	protected function initialize(): void {
		parent::initialize();
		$this->arguments_configuration_directory();
		$this->arguments_username();
	}

	/**
	 *
	 */
	private function arguments_configuration_directory(): void {
		$app = $this->application;
		$config_dir = $this->option("config_dir");
		if ($config_dir) {
			$config_dir = $app->paths->expand($config_dir);
			if (!is_dir($config_dir)) {
				$app->logger->warning("{class}::config_dir {config_dir} is not a directory", [
					"class" => get_class($this),
					"config_dir" => $config_dir,
				]);
			}
		} else {
			$config_dir = $app->paths->home(".subversion");
		}
		if (!$config_dir) {
			return;
		}
		$this->arguments .= " --config-dir " . escapeshellarg($config_dir) . " ";
	}

	/**
	 *
	 */
	private function arguments_username(): void {
		$app = $this->application;
		$username = $this->option("username");
		if (!$username) {
			return;
		}
		$this->arguments .= " --username " . escapeshellarg($username) . " ";
	}

	/**
	 * First column: Says if item was added, deleted, or otherwise changed
	 *
	 * ' ' no modifications
	 * 'A' Added
	 * 'C' Conflicted
	 * 'D' Deleted
	 * 'I' Ignored
	 * 'M' Modified
	 * 'R' Replaced
	 * 'X' an unversioned directory created by an externals definition
	 * '?' item is not under version control
	 * '!' item is missing (removed by non-svn command) or incomplete
	 * '~' versioned item obstructed by some item of a different kind
	 *
	 * Second column: Modifications of a file's or directory's properties
	 *
	 * ' ' no modifications
	 * 'C' Conflicted
	 * 'M' Modified
	 *
	 * Third column: Whether the working copy is locked for writing by
	 * another Subversion client modifying the working copy
	 *
	 * ' ' not locked for writing
	 * 'L' locked for writing
	 *
	 * Fourth column: Scheduled commit will contain addition-with-history
	 *
	 * ' ' no history scheduled with commit
	 * '+' history scheduled with commit
	 *
	 * Fifth column: Whether the item is switched or a file external
	 *
	 * ' ' normal
	 * 'S' the item has a Switched URL relative to the parent
	 * 'X' a versioned file created by an eXternals definition
	 *
	 * Sixth column: Whether the item is locked in repository for exclusive commit
	 * (without -u)
	 *
	 * ' ' not locked by this working copy
	 * 'K' locked by this working copy, but lock might be stolen or broken
	 * (with -u)
	 * ' ' not locked in repository, not locked by this working copy
	 * 'K' locked in repository, lock owned by this working copy
	 * 'O' locked in repository, lock owned by another working copy
	 * 'T' locked in repository, lock owned by this working copy was stolen
	 * 'B' not locked in repository, lock owned by this working copy is broken
	 *
	 * Seventh column: Whether the item is the victim of a tree conflict
	 * ' ' normal
	 * 'C' tree-Conflicted
	 *
	 * @param array $targets
	 *
	 * Sample:
	 *
	 * ?       modernizr
	 * ?       moment
	 * ?       nouislider
	 * ?       openlayers
	 * ?       paymentinfo
	 * ?       respondjs
	 * ?       spectrum
	 * ?       tinymce
	 * ?       underscorejs
	 * ?       yellow_text
	 *
	 * @deprecated immediately once status works
	 */
	public function status_plaintext($target = null, $updates = false) {
		$extras = $this->option('status_arguments', '');
		if ($extras) {
			$extras = " $extras";
		}
		$target = $this->path($target);
		// TODO use --xml here to handle parsing more robustly
		$command = $updates ? "status -u$extras {target}" : "status$extras {target}";
		$result = $this->run_command($command, [
			'target' => $target,
		]);
		$matches = null;
		if (!preg_match_all('/^([ ACDIMRX?!~])([ CM])([ L])([ +])([ SX])([ KOTB])([ C]) ([^\s]+)\n/', $result, $matches)) {
			return [];
		}
		$results = [];
		foreach ($matches as $match) {
			$result = ArrayTools::map_keys($match, [
				0 => "raw_status_line",
				1 => "changed",
				2 => "directory-properties-changed",
				3 => "locked-working",
				4 => "addition-with-history",
				5 => "switched",
				6 => "locked-repo",
				7 => "conflict",
				8 => "path",
			]);
			$results[$result['path']] = ArrayTools::clean($result, " ");
		}
		return $results;
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Repository::status()
	 */
	public function status($target = null, $updates = false) {
		$extras = $this->option('status_arguments', '');
		if ($extras) {
			$extras = " $extras";
		}
		$target = $this->resolve_target($target);
		$command = $updates ? "status -u$extras --xml {target}" : "status$extras --xml {target}";
		$result = $this->run_command($command, [
			'target' => $target,
		]);
		$xml = new \SimpleXMLElement(implode("\n", $result));
		$results = [];
		foreach ($xml->xpath("//entry") as $entry) {
			/* @var $item \SimpleXMLElement */
			$attributes = $entry->attributes();
			$path = strval($attributes['path']);
			$wc_status = first($entry->xpath("wc-status"));
			$entry_result = [];
			if ($wc_status) {
				$wc_status_attributes = $wc_status->attributes();
				$svn_status = strval($wc_status_attributes['item']);
				if (array_key_exists($svn_status, self::$svn_status_map)) {
					$entry_result[self::ENTRY_STATUS] = self::$svn_status_map[$svn_status];
				} else {
					$entry_result[self::ENTRY_STATUS] = self::STATUS_CUSTOM;
				}
				$entry_result['svn_props'] = strval($wc_status_attributes['props']);
				$entry_result['svn_status'] = $svn_status;
				$entry_result[self::ENTRY_VERSION] = strval($wc_status_attributes['revision']);
			} else {
				$entry_result[self::ENTRY_STATUS] = self::STATUS_UNKNOWN;
				$entry_result[self::ENTRY_MESSAGE] = "No wc-status XML child of entry";
			}
			$results[$path] = $entry_result;
		}
		return $results;
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Repository::commit()
	 */
	public function commit($target = null, $message = null): void {
		$this->sync($target);
		$this->run_command("commit -m {message}", [
			"message" => escapeshellarg($message),
		]);
	}

	/**
	 * Update for subversion will change its action based on the current filesystem state:
	 *
	 * - New, empty repository - never initialized: checkout
	 * - Existing repository which matches current URL: update
	 * - Existing repository which does NOT match current URL: switch
	 *
	 * The main intention is that if you run "update" you want the repository to match the URL
	 * configured.
	 *
	 * {@inheritDoc}
	 * @see \zesk\Repository::update()
	 */
	public function update($target = null) {
		if (!$this->validate()) {
			return $this->run_command("checkout {url} {target}", [
				"url" => $this->url(),
				"target" => $this->path($target),
			]);
		} else {
			if (!$this->url_matches()) {
				if (!empty($target)) {
					throw new Exception_Semantics("Can not update repository from an internal target until root is switched to desired url {desired_url}", [
						"desired_url" => $this->url,
					]);
				}
				return $this->run_command("switch --ignore-ancestry {url}", [
					"url" => $this->url,
				]);
			} else {
				return $this->run_command("update --force {target}", [
					"target" => $this->resolve_target($target),
				]);
			}
		}
	}

	/**
	 * Returns true if URLs match
	 *
	 * @return boolean
	 */
	private function url_matches() {
		$repo_url = $this->normalize_url($this->info(null, self::INFO_URL));
		$url = $this->normalize_url($this->url());
		return ($repo_url === $url);
	}

	/**
	 * Are there remote changes which need to be updated?
	 *
	 * @param string $target
	 * @return boolean
	 */
	public function need_update($target = null) {
		if (!$this->validate()) {
			return true;
		}
		if (!$this->url_matches()) {
			return true;
		}
		$target = $this->resolve_target($target);
		$results = $this->status($target, true);
		if (count($results) > 0) {
			return true;
		}
		return false;
	}

	/**
	 * Are there local changes which could be committed?
	 *
	 * @param string $target
	 * @return boolean
	 * @throws Exception_Semantics
	 */
	public function need_commit($target = null) {
		if (!$this->validate()) {
			throw new Exception_Semantics("{method} can only be called on a valid repository", [
				"method" => __METHOD__,
			]);
		}
		$target = $this->resolve_target($target);
		$status = $this->status($target);
		if (count($status) > 0) {
			$this->application->logger->error("SVN working copy at {target} is out of date with the repository: {files}", [
				"target" => $target,
				"files" => array_keys($status),
			]);
			return true;
		}
		return false;
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Repository::rollback()
	 */
	public function rollback($target = null) {
		$extras = $this->option('revert_arguments', '');
		if ($extras) {
			$extras = " $extras";
		}
		$command = "revert$extras {target}";
		$result = $this->run_command($command, [
			"target" => $target,
		]);
		if (!empty($result)) {
			$this->application->logger->error("SVN revert failed for {target}:\n{output}", [
				'target' => $target,
				'output' => $result,
			]);
			return false;
		}
		return true;
	}

	/**
	 *
	 * @param unknown $target
	 * @return boolean
	 */
	protected function sync($target) {
		$status = $this->status($target);
		$errors = "";
		foreach ($status as $f => $entry) {
			$changed = $entry['changed'];
			$args = [
				"file" => $f,
			];
			if ($changed === '?') {
				$errors .= $this->run_command("add {file}", $args);
			} elseif ($changed === '!') {
				$errors .= $this->run_command("remove {file}", $args);
			}
		}
		if (!empty($errors)) {
			$this->application->logger->error("SVN synchronization failed for {target}:\n{output}", [
				'target' => $target,
				'output' => $errors,
			]);
			return false;
		}
		return true;
	}

	/**
	 * Fetch info for $path
	 *
	 * @param $path string Absolute or relative path to retrieve
	 */
	private function _info($path = null) {
		$xml = implode("\n", $this->run_command("info --xml {path}", [
			"path" => strval($path),
		]));
		$parsed = new \SimpleXMLElement($xml);
		foreach ([
			"url" => "url",
			"relative-url" => "relative-url",
			"repository/root" => "root",
			"repository/uuid" => "uuid",
			"wc-info/wcroot-abspath" => "working-copy-path",
			"wc-info/schedule" => "working-copy-schedule",
			"wc-info/depth" => "working-copy-depth",
			"commit/author" => "commit-author",
			"commit/date" => "commit-date",
		] as $xpath => $key) {
			$result[$key] = strval($parsed->xpath("//entry/" . $xpath)[0]);
		}
		return $result;
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Repository::info()
	 */
	public function info($target = null, $component = null) {
		if (!$this->validate()) {
			throw new Exception_Semantics("Repository is not initialized");
		}
		$path = $this->resolve_target($target);

		try {
			$xml = implode("\n", $this->run_command("info --xml {path}", [
				"path" => strval($path),
			]));
		} catch (\Exception $e) {
			return [];
		}
		$parsed = new \SimpleXMLElement($xml);
		foreach ([
			"url" => "url",
			"relative-url" => "relative-url",
			"repository/root" => "root",
			"repository/uuid" => "uuid",
			"wc-info/wcroot-abspath" => "working-copy-path",
			"wc-info/schedule" => "working-copy-schedule",
			"wc-info/depth" => "working-copy-depth",
			"commit/author" => self::ENTRY_AUTHOR,
			"commit/date" => self::ENTRY_DATE,
		] as $xpath => $key) {
			$result[$key] = strval($parsed->xpath("//entry/" . $xpath)[0]);
		}
		if ($component) {
			return avalue($result, $component, null);
		}
		return $result;
	}

	/**
	 *
	 * @param string $url
	 * @return string
	 */
	public function tags_from_url($url) {
		$trunk_directory = $this->option("trunk_directory", "trunk");
		$trunk_directory = "/$trunk_directory/";

		$branches_directory = $this->option("branches_directory", "branches");
		$branches_directory = "/$branches_directory/";

		$tags_directory = $this->option("tags_directory", "tags");
		$tags_directory = "/$tags_directory/";

		// Make sure we end with a slash
		$url = rtrim($url, "/") . "/";
		$min = $mintoken = null;
		foreach ([
			$trunk_directory,
			$tags_directory,
			$branches_directory,
		] as $token) {
			$pos = strpos($url, $token);
			if ($pos !== false) {
				if ($min === null || $pos < $min) {
					$min = $pos;
					$mintoken = $token;
				}
			}
		}
		if ($min === null) {
			return rtrim($url, "/") . $tags_directory;
		}
		return StringTools::left($url, $mintoken) . $tags_directory;
	}

	/**
	 * Determine the latest version of this repository by scanning the tags directory.
	 *
	 * {@inheritDoc}
	 * @see \zesk\Repository::latest_version()
	 */
	public function latest_version() {
		return $this->compute_latest_version($this->versions());
	}

	/**
	 * Normalize URL and strip trailing slash, if any.
	 *
	 * @param string $url
	 * @return string
	 */
	private function normalize_url($url) {
		$url = URL::normalize($url);
		return rtrim($url, "/");
	}

	/**
	 * Returns versions from the most recent to the oldest
	 *
	 * {@inheritDoc}
	 * @see \zesk\Repository::versions()
	 */
	public function versions() {
		$url = $this->url;
		if (!$url) {
			$info = $this->_info(".");
			$url = $info['url'];
		}
		if (!$url) {
			throw new Exception_Semantics("Need a URL to retrieve versions");
		}
		$tags = $this->tags_from_url($url);
		$versions = $this->run_command("list {tags}", [
			"tags" => $tags,
		]);
		return $this->rsort_versions(ArrayTools::trim($versions, "./"));
	}
}
