<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage git
 * @author kent
 * @copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk\Git;

use aws\classes\Module;
use zesk\Exception_Unsupported;

/**
 * Git Repository implementation
 *
 * @see Module
 * @author kent
 */
class Repository extends \zesk\Repository_Command {
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
	 * @throws Exception_Unsupported
	 */
	public function info(string $path = '', string $component = ''): array {
		throw new Exception_Unsupported(__METHOD__);
	}

	/**
	 * Does the target need to be updated?
	 *
	 * @param string $target
	 * @return boolean
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

	public function need_commit(string $target): bool {
	}

	/**
	 * Synchronizes all files beneath $target with repository.
	 *
	 * @param string $target
	 * @param string $message
	 */
	public function commit(string $target, string $message): bool {
	}

	/**
	 * Update repository target at target, and get changes from remote
	 *
	 * @param string $target
	 */
	public function update(string $target): void {
	}

	/**
	 * Undo changes to a target and reset to current branch/tag
	 *
	 * @param string $target Directory of target directory
	 * @return boolean
	 */
	public function rollback(string $target): void {
	}

	/**
	 * @return string
	 * @throws \Exception
	 */
	public function latest_version(): string {
		$versions = $this->run_command('tag');
		return $this->compute_latest_version($versions);
	}

	/**
	 *
	 * In the short-format, the status of each path is shown as
	 *
	 * XY PATH1 -> PATH2
	 *
	 * where PATH1 is the path in the HEAD, and the " -> PATH2" part is shown only when PATH1 corresponds
	 * to a different path in the index/worktree (i.e. the file is renamed). The XY is a two-letter status
	 * code.
	 *
	 * The fields (including the ->) are separated from each other by a single space. If a filename
	 * contains whitespace or other nonprintable characters, that field will be quoted in the manner of a
	 * C string literal: surrounded by ASCII double quote (34) characters, and with interior special
	 * characters backslash-escaped.
	 *
	 * For paths with merge conflicts, X and Y show the modification states of each side of the merge. For
	 * paths that do not have merge conflicts, X shows the status of the index, and Y shows the status of
	 * the work tree. For untracked paths, XY are ??. Other status codes can be interpreted as follows:
	 *
	 * o   ' ' = unmodified
	 *
	 * o   M = modified
	 *
	 * o   A = added
	 *
	 * o   D = deleted
	 *
	 * o   R = renamed
	 *
	 * o   C = copied
	 *
	 * o   U = updated but unmerged
	 *
	 * Ignored files are not listed, unless --ignored option is in effect, in which case XY are !!.
	 *
	 * X          Y     Meaning
	 * -------------------------------------------------
	 * [MD]   not updated
	 * M        [ MD]   updated in index
	 * A        [ MD]   added to index
	 * D         [ M]   deleted from index
	 * R        [ MD]   renamed in index
	 * C        [ MD]   copied in index
	 * [MARC]           index and work tree matches
	 * [ MARC]     M    work tree changed since index
	 * [ MARC]     D    deleted in work tree
	 * -------------------------------------------------
	 * D           D    unmerged, both deleted
	 * A           U    unmerged, added by us
	 * U           D    unmerged, deleted by them
	 * U           A    unmerged, added by them
	 * D           U    unmerged, deleted by us
	 * A           A    unmerged, both added
	 * U           U    unmerged, both modified
	 * -------------------------------------------------
	 * ?           ?    untracked
	 * !           !    ignored
	 * -------------------------------------------------
	 *
	 * If -b is used the short-format status is preceded by a line
	 *
	 * ## branchname tracking info
	 *
	 */
	private function parse_status_results(array $results) {
		$parsed_results = [];
		foreach ($results as $line) {
		}
		return $parsed_results;
	}
}
