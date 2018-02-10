<?php
/**
 * @package zesk
 * @subpackage subversion
 * @author kent
 * @copyright &copy; 2017 Market Acumen, Inc.
 */
namespace zesk\Subversion;

use zesk\StringTools;
use zesk\ArrayTools;

/**
 *
 * @author kent
 *
 */
class Repository extends \zesk\Repository_Command {
	/**
	 *
	 * @var array
	 */
	protected $info = null;

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
	 * Non-interactive
	 *
	 * @var string
	 */
	protected $arguments = " --non-interactive";
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
	public function status($target = null, $updates = false) {
		$extras = $this->option('status_arguments', '');
		if ($extras) {
			$extras = " $extras";
		}
		$target = $this->path($target);
		$command = $updates ? "status -u$extras {target}" : "status$extras {target}";
		$result = $this->run_command($command, array(
			'target' => $target
		));
		$matches = null;
		if (!preg_match_all('/^([ ACDIMRX?!~])([ CM])([ L])([ +])([ SX])([ KOTB])([ C]) ([^\s]+)\n/', $result, $matches)) {
			return array();
		}
		$results = array();
		foreach ($matches as $match) {
			$result = ArrayTools::map_keys($match, array(
				0 => "raw_status_line",
				1 => "changed",
				2 => "directory-properties-changed",
				3 => "locked-working",
				4 => "addition-with-history",
				5 => "switched",
				6 => "locked-repo",
				7 => "conflict",
				8 => "path"
			));
			$results[$result['path']] = ArrayTools::clean($result, " ");
		}
		return $results;
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Repository::commit()
	 */
	public function commit($target = null, $message = null) {
		$this->sync($target);
		$this->run_command("commit -m {message}", array(
			"message" => escapeshellarg($message)
		));
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Repository::update()
	 */
	public function update($target = null) {
		$this->run_command("update {target}", array(
			"target" => $this->path($target)
		));
	}

	/**
	 * Run before target is updated with new data
	 *
	 * (non-PHPdoc)
	 * @see Repository::pre_update()
	 */
	function pre_update($target = null) {
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
			$this->application->logger->error("SVN working copy at {target} has local modifications: {files}", array(
				"target" => $target,
				"files" => array_keys($status)
			));
			return false;
		}
		$status = $this->status($target = null, true);
		if (count($status) > 0) {
			$this->application->logger->error("SVN working copy at {target} is out of date with the repository: {files}", array(
				"target" => $target,
				"files" => array_keys($status)
			));
			return false;
		}
		return true;
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Repository::rollback()
	 */
	function rollback($target = null) {
		$extras = $this->option('revert_arguments', '');
		if ($extras) {
			$extras = " $extras";
		}
		$command = "revert$extras {target}";
		$result = $this->run_command($command, array(
			"target" => $target
		));
		if (!empty($result)) {
			$this->application->logger->error("SVN revert failed for {target}:\n{output}", array(
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
	function post_update($target = null) {
		$this->sync($target);
	}

	/**
	 *
	 * @param unknown $target
	 * @return boolean
	 */
	protected function sync($target) {
		$status = $this->status($target);
		$errors = "";
		foreach ($status as $f => $attributes) {
			$changed = $attributes['changed'];
			$args = array(
				"file" => $f
			);
			if ($changed === '?') {
				$errors .= $this->run_command("add {file}", $args);
			} else if ($changed === '!') {
				$errors .= $this->run_command("remove {file}", $args);
			}
		}
		if (!empty($errors)) {
			$this->application->logger->error("SVN synchronization failed for {target}:\n{output}", array(
				'target' => $target,
				'output' => $errors
			));
			return false;
		}
		return true;
	}
	public function _info() {
		if ($this->info) {
			return $this->info;
		}
		$xml = implode("\n", $this->run_command("info --xml"));
		$parsed = new \SimpleXMLElement($xml);
		dump($parsed);
		die(__FILE__);
		return $this->info;
	}

	/**
	 *
	 * @param unknown $url
	 * @return NULL|string
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
		foreach (array(
			$trunk_directory,
			$tags_directory,
			$branches_directory
		) as $token) {
			$pos = strpos($url, $token);
			if ($pos !== false) {
				if ($min === null || $pos < $min) {
					$min = $pos;
					$mintoken = $token;
				}
			}
		}
		if ($min === null) {
			return null;
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
		$info = $this->_info();
		$tags = $this->tags_from_url($info['url']);
		$versions = $this->run_command("list {tags}", array(
			"tags" => $tags
		));
		return $this->compute_latest_version($versions);
	}
}
