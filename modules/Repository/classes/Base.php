<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage repository
 * @author kent
 * @copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Repository;

use zesk\Application;
use zesk\Directory;
use zesk\Exception_Class_NotFound;
use zesk\Exception_NotFound;
use zesk\Exception_Semantics;
use zesk\Exception_Syntax;
use zesk\Hookable;
use zesk\URL;

/**
 * @see Command
 * @see \zesk\Subversion\Repository
 * @see \zesk\Git\Repository
 * @author kent
 */
abstract class Base extends Hookable {
	/**
	 * When setting the path, find valid parent directory which appears to be the repository root. Value is a boolean (true/false).
	 *
	 * Default value is false.
	 *
	 * @var string
	 */
	public const OPTION_FIND_ROOT = 'find_root';

	/**
	 * File's status
	 *
	 * Keys for structure returned by Repository::status
	 *
	 * @var string
	 */
	public const ENTRY_STATUS = 'status';

	/**
	 * The version of a commit
	 *
	 * Keys for structure returned by Repository::status
	 *
	 * @var string
	 */
	public const ENTRY_VERSION = 'version';

	/**
	 * A particular commit's author
	 *
	 * Keys for structure returned by Repository::status
	 *
	 * @var string
	 */
	public const ENTRY_AUTHOR = 'commit-author';

	/**
	 * A particular commit's date
	 *
	 * Keys for structure returned by Repository::status
	 *
	 * @var string
	 */
	public const ENTRY_DATE = 'commit-date';

	/**
	 * Place for errors or messages about status
	 *
	 * Keys for structure returned by Repository::status
	 *
	 * @var string
	 */
	public const ENTRY_MESSAGE = 'message';

	/**
	 * A file has not been added to the repository yet
	 *
	 * @var string
	 */
	public const STATUS_UNVERSIONED = 'UNVERSIONED';

	/**
	 * File has been added but not committed
	 *
	 * @var string
	 */
	public const STATUS_ADDED = 'ADDED';

	/**
	 * A file has been removed
	 *
	 * @var string
	 */
	public const STATUS_REMOVED = 'REMOVED';

	/**
	 * Deleted in local, present in remote
	 *
	 * @var string
	 */
	public const STATUS_DELETED = 'DELETED';

	/**
	 * Not present in local, present in remote
	 *
	 * @var string
	 */
	public const STATUS_MISSING = 'MISSING';

	/**
	 * Status strings for entry status field
	 *
	 * @var string
	 */
	public const STATUS_MODIFIED = 'MODIFIED';

	/**
	 * Each entry has a custom status which should be referred to when the status is this
	 *
	 * @var string
	 */
	public const STATUS_CUSTOM = 'CUSTOM';

	/**
	 * Each entry has an unknown status due to some error
	 *
	 * @var string
	 */
	public const STATUS_UNKNOWN = 'UNKNOWN';

	/**
	 * The current repository's URL
	 *
	 * @see \Git\classes\Repository::info
	 * @var string
	 */
	public const INFO_URL = 'url';

	/**
	 * Override in subclasses
	 *
	 * @var string
	 */
	protected string $code;

	/**
	 *
	 * @var string
	 */
	protected string $path;

	/**
	 *
	 * @var string
	 */
	protected string $url = '';

	/**
	 *
	 * @param Application $application
	 * @param string $root Path to repository root directory or a file within the repository
	 * @param array $options
	 */
	final public function __construct(Application $application, $root = null, array $options = []) {
		parent::__construct($application, $options);
		$this->inheritConfiguration();
		if (is_string($root) && !empty($root)) {
			$this->setPath($root);
		}
		$this->initialize();
	}

	/**
	 *
	 * @param string $suffix
	 * @return string
	 * @throws Exception_Semantics
	 */
	public function path(string $suffix = ''): string {
		if (!$this->path) {
			throw new Exception_Semantics('Need to set the path before using path call');
		}
		return path($this->path, $suffix);
	}

	/**
	 * Given a path, convert to an absolute path and check it's a proper subdirectory
	 *
	 * @param string $target
	 * @return string
	 * @throws Exception_Semantics
	 */
	protected function resolve_target(string $target = ''): string {
		if (empty($target)) {
			return $this->path;
		}
		$final_path = Directory::isAbsolute($target) ? $target : $this->path($target);
		$final_path = realpath($final_path);
		if (!str_starts_with($this->path, $final_path)) {
			throw new Exception_Semantics('Passed absolute path {target} (-> {final_path}) must be a subdirectory of {path}', [
				'target' => $target, 'final_path' => $final_path, 'path' => $final_path,
			]);
		}
		return $final_path;
	}

	/**
	 * @param string $path
	 * @return Base
	 */
	public function setPath(string $path): self {
		$this->path = $path;
		return $this;
	}

	/**
	 *
	 */
	protected function initialize(): void {
	}

	/**
	 * Code name for this repository
	 *
	 * @return string
	 */
	final public function code(): string {
		return $this->code;
	}

	/**
	 *
	 * @param Application $application
	 * @param string $type
	 * @param string|null $root
	 * @param array $options
	 * @return Base
	 * @throws Exception_NotFound
	 */
	public static function factory(Application $application, string $type, string $root = null, array $options = []):
	Base {
		$repo = $application->repository_module();
		$class = $repo->findRepository($type);

		try {
			$object = $application->objects->factory($class, $application, $root, $options);
			assert($object instanceof Base);
			return $object;
		} catch (Exception_Class_NotFound $e) {
			throw new Exception_NotFound('{class} not found', ['class' => $class], 0, $e);
		}
	}

	/**
	 * Override in subclasses for special behavior
	 *
	 * @param string $url
	 * @return boolean
	 */
	protected function validate_url(string $url): bool {
		return URL::valid($url);
	}

	/**
	 * Setter/getter for the repository URL. Changing the URL does not update anything until update.
	 *
	 * @param string $set
	 * @return self
	 * @throws Exception_Syntax
	 */
	public function setURL(string $set): self {
		$set = URL::normalize($set);
		if (!$this->validate_url($set)) {
			throw new Exception_Syntax('Not a valid URL {url}', [
				'url' => $set,
			]);
		}
		$this->url = $set;
		return $this;
	}

	/**
	 * Setter/getter for the repository URL. Changing the URL does not update anything until update.
	 *
	 * @return string
	 * @throws Exception_Semantics
	 */
	public function url(): string {
		if ($this->url) {
			return $this->url;
		}
		if (!$this->validate()) {
			throw new Exception_Semantics('No repository configured, must manually set URL before calling {method}', [
				'method' => __METHOD__,
			]);
		}
		$parts = $this->info('');
		return $parts[self::INFO_URL] ?? '';
	}

	/**
	 * Retrieve repository-specific information
	 *
	 * @param string $path Path to get info about
	 * @return array
	 */
	abstract public function info(string $path): array;

	/**
	 * Check if the directory is a valid directory for this repository
	 *
	 * @return boolean
	 */
	abstract public function validate(): bool;

	/**
	 * Fetch a list of repository status for a target
	 *
	 * @param string $target
	 * @param boolean $updates
	 *
	 * @return array[]
	 */
	abstract public function status(string $target, bool $updates = false): array;

	/**
	 * Does the target have changes which need to be sent to the remote?
	 *
	 * @param string $target
	 * @return boolean
	 */
	abstract public function need_commit(string $target): bool;

	/**
	 * Synchronizes all files beneath $target with repository.
	 *
	 * @param string $target
	 * @param string $message
	 */
	abstract public function commit(string $target, string $message): bool;

	/**
	 * Does the target need to be updated?
	 *
	 * @param string $target
	 * @return boolean
	 */
	abstract public function needUpdate(string $target): bool;

	/**
	 * Update repository and get changes from remote
	 *
	 * @param string $target
	 */
	abstract public function update(string $target): void;

	/**
	 * Undo changes to a target and reset to current branch/tag
	 *
	 * @param string $target Directory of target directory
	 * @return boolean
	 */
	abstract public function rollback(string $target): void;

	/**
	 * Return the latest version string for this repository. Should mimic `zesk version` formatting.
	 *
	 * @return string
	 */
	abstract public function latest_version(): string;

	/**
	 * @return string[]
	 */
	public function versions(): array {
		return [];
	}
}
