<?php
/**
 * @package zesk
 * @subpackage repository
 * @author kent
 * @copyright &copy; 2017 Market Acumen, Inc.
 */
namespace zesk;

/**
 * @see \zesk\Repository_Command
 * @see \zesk\Subversion\Repository
 * @see \zesk\Git\Repository
 * @author kent
 */
abstract class Repository extends Hookable {
	/**
	 * When setting the path, find valid parent directory which appears to be the repository root. Value is a boolean (true/false).
	 *
	 * Default value is false.
	 *
	 * @var string
	 */
	const OPTION_FIND_ROOT = "find_root";

	/**
	 * File's status
	 *
	 * Keys for structure returned by Repository::status
	 *
	 * @var string
	 */
	const ENTRY_STATUS = "status";

	/**
	 * The version of a commit
	 *
	 * Keys for structure returned by Repository::status
	 *
	 * @var string
	 */
	const ENTRY_VERSION = "version";

	/**
	 * A particular commit's author
	 *
	 * Keys for structure returned by Repository::status
	 *
	 * @var string
	 */
	const ENTRY_AUTHOR = "commit-author";

	/**
	 * A particular commit's date
	 *
	 * Keys for structure returned by Repository::status
	 *
	 * @var string
	 */
	const ENTRY_DATE = "commit-date";

	/**
	 * Place for errors or messages about status
	 *
	 * Keys for structure returned by Repository::status
	 *
	 * @var string
	 */
	const ENTRY_MESSAGE = "message";

	/**
	 * A file has not been added to the repository yet
	 *
	 * @var string
	 */
	const STATUS_UNVERSIONED = "UNVERSIONED";

	/**
	 * File has been added but not committed
	 *
	 * @var string
	 */
	const STATUS_ADDED = "ADDED";

	/**
	 * A file has been removed
	 *
	 * @var string
	 */
	const STATUS_REMOVED = "REMOVED";

	/**
	 * Deleted in local, present in remote
	 *
	 * @var string
	 */
	const STATUS_DELETED = "DELETED";

	/**
	 * Not present in local, present in remote
	 *
	 * @var string
	 */
	const STATUS_MISSING = "MISSING";

	/**
	 * Status strings for entry status field
	 *
	 * @var string
	 */
	const STATUS_MODIFIED = "MODIFIED";

	/**
	 * Each entry has a custom status which should be referred to when the status is this
	 *
	 * @var string
	 */
	const STATUS_CUSTOM = "CUSTOM";

	/**
	 * Each entry has an unknown status due to some error
	 *
	 * @var string
	 */
	const STATUS_UNKNOWN = "UNKNOWN";

	/**
	 * The current repository's URL
	 *
	 * @see Repository::info
	 * @var string
	 */
	const INFO_URL = "url";

	/**
	 * Override in subclasses
	 *
	 * @var string
	 */
	protected $code = null;

	/**
	 *
	 * @var string
	 */
	protected $path = null;

	/**
	 *
	 * @var string
	 */
	protected $url = null;

	/**
	 *
	 * @param Application $application
	 * @param string $root Path to repository root directory or a file within the repository
	 * @param array $options
	 */
	final public function __construct(Application $application, $root = null, array $options = array()) {
		parent::__construct($application, $options);
		$this->inherit_global_options();
		if (is_string($root) && !empty($root)) {
			$this->set_path($root);
		}
		$this->initialize();
	}

	/**
	 *
	 * @param string $suffix
	 * @return string
	 */
	public function path($suffix = null) {
		if (!$this->path) {
			throw new Exception_Semantics("Need to set the path before using path call");
		}
		return path($this->path, $suffix);
	}

	/**
	 * Given a path, convert to an absolute path and check it's a proper subdirectory
	 *
	 * @param string $suffix
	 * @throws Exception_Semantics
	 * @return NULL|string
	 */
	protected function resolve_target($target = null) {
		if (empty($target)) {
			return $this->path;
		}
		$final_path = Directory::is_absolute($target) ? $target : $this->path($target);
		$final_path = realpath($final_path);
		if (!begins($this->path, $final_path)) {
			throw new Exception_Semantics("Passed absolute path {target} must be a subdirectory of {path}", array(
				"target" => $target,
				"path" => $path,
			));
		}
		return $final_path;
	}

	/**
	 * @param string $path
	 * @return \zesk\Repository
	 */
	public function set_path($path) {
		$this->path = $path;
		return $this;
	}

	/**
	 *
	 */
	protected function initialize() {
	}

	/**
	 * Code name for this repository
	 *
	 * @return string
	 */
	final public function code() {
		return $this->code;
	}

	/**
	 *
	 * @param Application $application
	 * @param unknown $type
	 * @return NULL|Repository
	 */
	public static function factory(Application $application, $type, $root = null, array $options = array()) {
		try {
			$repo = $application->modules->object("Repository");
			/* @var $repo Module_Repository */
			$class = $repo->find_repository($type);
			if (!$class) {
				return null;
			}
			return $application->objects->factory($class, $application, $root, $options);
		} catch (Exception_Class_NotFound $e) {
			return null;
		}
	}

	/**
	 * Override in subclasses for special behavior
	 *
	 * @param string $url
	 * @return boolean
	 */
	protected function validate_url($url) {
		return URL::valid($url);
	}

	/**
	 * Setter/getter for the repository URL. Changing the URL does not update anything until update.
	 *
	 * @param string $set
	 * @return string|self
	 */
	public function url($set = null) {
		if ($set !== null) {
			if (empty($set)) {
				$this->url = null;
				return $this;
			}
			if (!$this->validate_url($set)) {
				throw new Exception_Syntax("Not a valid URL {url}", array(
					"url" => $set,
				));
			}
			$this->url = $set;
			return $this;
		}
		if ($this->url) {
			return $this->url;
		}
		if (!$this->validate()) {
			throw new Exception_Semantics("No repository configured, must manually set URL before calling {method}", array(
				"method" => __METHOD__,
			));
		}
		return $this->info(null, self::INFO_URL);
	}

	/**
	 * Retrieve repository-specific information
	 *
	 * @param string $path Path to get info about
	 * @param string $component Retrieve this component of the info
	 * @return array|string
	 */
	abstract public function info($path = null, $component = null);

	/**
	 * Check if the directory is a valid directory for this repository
	 *
	 * @param string $directory
	 * @return boolean
	 */
	abstract public function validate();

	/**
	 * Fetch a list of repository status for a target
	 *
	 * @param string $target
	 * @param boolean $updates
	 *
	 * @return array[]
	 */
	abstract public function status($target = null, $updates = false);

	/**
	 * Does the target have changes which need to be sent to the remote?
	 *
	 * @param string $target
	 * @return boolean
	 */
	abstract public function need_commit($target = null);

	/**
	 * Synchronizes all files beneath $target with repository.
	 *
	 * @param string $target
	 * @param string $message
	 */
	abstract public function commit($target = null, $message = null);

	/**
	 * Does the target need to be updated?
	 *
	 * @param string $target
	 * @return boolean
	 */
	abstract public function need_update($target = null);

	/**
	 * Update repository and get changes from remote
	 *
	 * @param string $target
	 */
	abstract public function update($target = null);

	/**
	 * Undo changes to a target and reset to current branch/tag
	 *
	 * @param string $target Directory of target directory
	 * @return boolean
	 */
	abstract public function rollback($target = null);

	/**
	 * Return the latest version string for this repository. Should mimic `zesk version` formatting.
	 *
	 * @return string
	 */
	abstract public function latest_version();
}
