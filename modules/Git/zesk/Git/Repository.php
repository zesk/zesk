<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage git
 * @author kent
 * @copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Git;

use aws\classes\Module;
use zesk\CommandFailed;
use zesk\Exception\Semantics;
use zesk\Exception\Unimplemented;
use zesk\Exception\Unsupported;
use zesk\Repository\CommandBase;

/**
 * Git Repository implementation
 *
 * @see Module
 * @author kent
 */
class Repository extends CommandBase {
	/**
	 * @var string
	 */
	protected string $code = 'git';

	/**
	 *
	 * @var string
	 */
	protected string $executable = 'git';

	/**
	 * Used in validate function
	 *
	 * @var string
	 */
	protected string $dot_directory = '.git';

	/**
	 * Fetch a list of repository status for a target
	 *
	 * @param string $target
	 * @param bool $updates
	 * @return array
	 */
	public function status(string $target, bool $updates = false): array {
		return [];
	}

	/**
	 * @param string $path
	 * @param string $component
	 * @return array
	 * @throws Unsupported
	 */
	public function info(string $path = '', string $component = ''): array {
		throw new Unsupported(__METHOD__);
	}

	/**
	 * Does the target need to be updated?
	 *
	 * @param string $target
	 * @return boolean
	 * @throws Semantics|CommandFailed
	 */
	public function needUpdate(string $target): bool {
		if (!$this->validate()) {
			return true;
		}
		$tag = 'origin/master';
		$target = $this->resolve_target($target);
		$result = $this->run_command('diff --shortstat {tag} {target}', [
			'tag' => $tag,
			'target' => $target,
		]);
		if (count($result) === 0) {
			return false;
		}
		return true;
	}

	/**
	 * @throws Unimplemented
	 */
	public function need_commit(string $target): bool {
		throw new Unimplemented(__METHOD__);
	}

	/**
	 * Synchronizes all files beneath $target with repository.
	 *
	 * @param string $target
	 * @param string $message
	 * @throws Unimplemented
	 */
	public function commit(string $target, string $message): bool {
		throw new Unimplemented(__METHOD__);
	}

	/**
	 * Update repository target at target, and get changes from remote
	 *
	 * @param string $target
	 * @throws Unimplemented
	 */
	public function update(string $target): void {
		throw new Unimplemented(__METHOD__);
	}

	/**
	 * Undo changes to a target and reset to current branch/tag
	 *
	 * @param string $target Directory of target directory
	 * @return void
	 * @throws Unimplemented
	 */
	public function rollback(string $target): void {
		throw new Unimplemented(__METHOD__);
	}

	/**
	 * @return string
	 * @throws CommandFailed
	 */
	public function latest_version(): string {
		$versions = $this->run_command('tag');
		return $this->compute_latest_version($versions);
	}

	/**
	 * @return bool
	 */
	public function validate(): bool {
		return parent::validate();
	}
}
