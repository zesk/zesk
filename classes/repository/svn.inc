<?php

class Repository_SVN extends Repository {

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
	 */
	public function status($target, $updates = false) {
		$extras = $this->option('status_arguments', '');
		if ($extras) {
			$extras = " $extras";
		}
		$command = $updates ? "svn status -u$extras {0}" : "svn status$extras {0}";
		$result = zesk::execute($command, $target);
		$matches = null;
		if (!preg_match_all('/^([ ACDIMRX?!~])([ CM])([ L])([ +])([ SX])([ KOTB])([ C]) ([^\s]+)\n/', $result, $matches)) {
			return array();
		}
		$results = array();
		foreach ($matches as $match) {
			$result = arr::map_keys($match, array(
				0 => "status_line",
				1 => "changed",
				2 => "directory-properties-changed",
				3 => "locked-working",
				4 => "addition-with-history",
				5 => "switched",
				6 => "locked-repo",
				7 => "conflict",
				8 => "path"
			));
			$results[$result['path']] = arr::clean($result, " ");
		}
		return $results;
	}

	/**
	 * Run before target is updated with new data
	 *
	 * (non-PHPdoc)
	 * @see Repository::pre_update()
	 */
	function pre_update($target) {
		$status = $this->status($target);
		$my_status = array();
		if (array_key_exists($target, $status)) {
			$my_status = $status[$target];
			if ($my_status['changed'] === '?') {
				// Directory never added. Will add.
				return true;
			}
		}
		if (count($status) > 0) {
			log::error("SVN working copy at {target} has local modifications: {files}", array(
				"target" => $target,
				"files" => array_keys($status)
			));
			return false;
		}
		$status = $this->status($target, true);
		if (count($status) > 0) {
			log::error("SVN working copy at {target} is out of date with the repository: {files}", array(
				"target" => $target,
				"files" => array_keys($status)
			));
			return false;
		}
		return true;
	}

	function rollback($target) {
		$extras = $this->option('revert_arguments', '');
		if ($extras) {
			$extras = " $extras";
		}
		$command = "svn revert$extras {0}";
		$result = zesk::execute($command, $target);
		if (!empty($result)) {
			log::error("SVN revert failed for {target}:\n{output}", array(
				'target' => $target,
				'output' => $result
			));
			return false;
		}
		return true;
	}

	/**
	 * Run before target is updated with new data
	 *
	 * (non-PHPdoc)
	 * @see Repository::pre_update()
	 */
	function post_update($target) {
		$this->sync($target);
	}

	function sync($target) {
		$status = $this->status($target);
		$errors = "";
		foreach ($status as $f => $attributes) {
			$changed = $attributes['changed'];
			if ($changed === '?') {
				$errors .= zesk::execute("svn add {0}", $f);
			} else if ($changed === '!') {
				$errors .= zesk::execute("svn remove {0}", $f);
			}
		}
		if (!empty($errors)) {
			log::error("SVN synchronization failed for {target}:\n{output}", array(
				'target' => $target,
				'output' => $errors
			));
			return false;
		}
		return true;
	}
}
