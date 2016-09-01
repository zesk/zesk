<?php

namespace zest;

use \Object as Object;
use zesk\File as File;
use zesk\Timestamp as Timestamp;

/**
 * @see Class_Test
 * @author kent
 * @property id $id
 * @property zest\Project $project
 * @property string $name
 * @property string $path
 * @property string $class
 * @property zesk\Timestamp $created
 * @property zesk\Timestamp $deleted
 * @property zesk\Timestamp $last_run
 * @property boolean $last_status
 */
class Test extends Object {
	function full_path() {
		return path($this->project->path, $this->path);
	}
	function find_or_create() {
		if (($data = $this->find()) !== null) {
			return $this;
		}
		$this->_refresh_class_and_name();
		return $this->store();
	}
	private function _refresh_class_and_name() {
		$this->set_member("class", $class = $this->determine_class_name());
		if ($this->member_is_empty("name")) {
			$this->name = empty($class) ? basename($this->path) : $class;
		}
	}
	public function sync_with_disk() {
		if (!file_exists($this->full_path())) {
			$this->deleted = Timestamp::now();
			$this->store();
			return null;
		}
		$this->_refresh_class_and_name();
		return $this;
	}
	private function determine_class_name() {
		$full_path = $this->full_path();
		$contents = File::contents($full_path, null);
		if ($contents === null) {
			throw new \Exception_File_NotFound($full_path);
		}
		if (defined('TOKEN_PARSE')) {
			$tokens = token_get_all($contents, TOKEN_PARSE);
		} else {
			$tokens = token_get_all($contents);
		}
		$total = count($tokens);
		$classes = array();
		for ($index = 0; $index < $total; $index++) {
			$token = $tokens[$index];
			if ($token[0] === T_CLASS) {
				$classes[] = $this->next_token($tokens, $index, T_STRING);
			}
		}
		return implode(",", $classes);
	}
	private function next_token(array $tokens, &$index, $type) {
		$n = count($tokens);
		while ($index < $n) {
			if ($tokens[$index][0] === $type) {
				return $tokens[$index][1];
			}
			$index++;
		}
		return null;
	}
	private function dump_token(array $token) {
		echo token_name($token[0]) . ": " . $token[1] . "\n";
	}
}