<?php

namespace zest;

use zesk\Object;
use zesk\Timestamp;
use zesk\arr as arr;

/**
 * @see Class_Project
 * @author kent
 * @property integer $id
 * @property string $path
 * @property zesk\Timestamp $created
 * @property zesk\Timestamp $modified
 * @property zesk\Timestamp $tests_updated
 * @property zesk\Timestamp $executed
 * @property zesk\Timestamp $first_success
 * @property zesk\Timestamp $first_failure
 * @property zesk\Timestamp $last_success
 * @property zesk\Timestamp $last_failure
 * @property integer $stats_total
 * @property integer $stats_success
 * @property integer $stats_failure
 * @property integer $status
 * @property \Object_Iterator $tests
 */
class Project extends Object {
	/**
	 * Get path for project source fies
	 * 
	 * @return string
	 */
	public function path() {
		return $this->path;
	}
	
	/**
	 * @return Command_Test
	 */
	protected function command_test() {
		return new \Command_Test(null, $this->command_test_options());
	}
	
	/**
	 * @return array
	 */
	protected function command_test_options() {
		return array(
			"directory" => $this->path
		);
	}
	
	/**
	 * @return array
	 */
	private function _tests_on_disk() {
		return arr::unprefix($this->command_test()->command_list(), $this->path);
	}
	
	/**
	 * 
	 * @return \zest\Test[]
	 */
	private function _synchronize_with_disk() {
		$all_tests = array();
		$database_tests = $this->tests->to_array('path');
		$disk_tests = arr::flip_copy($this->_tests_on_disk());
		foreach ($database_tests as $path => $test) {
			/* @var $test Test */
			if (isset($disk_tests[$path])) {
				unset($disk_tests[$path]);
				$all_tests[$path] = $test;
			} else {
				if ($test->sync_with_disk()) {
					$all_tests[$path] = $test;
				}
			}
		}
		foreach ($disk_tests as $path) {
			$test = new Test(array(
				"path" => $path,
				"project" => $this
			));
			try {
				if ($test->find_or_create()) {
					$all_tests[$path] = $test;
				}
			} catch (Exception_File_NotFound $e) {
			}
		}
		return $all_tests;
	}
	
	/**
	 * 
	 * @param integer $seconds
	 * @return boolean
	 */
	private function updated_within($seconds) {
		if (!$this->tests_updated) {
			return false;
		}
		$refresh_if_updated_before = Timestamp::now()->add_unit(-$seconds, Timestamp::UNIT_SECOND);
		return $this->tests_updated->after($refresh_if_updated_before);
	}
	
	/**
	 * 
	 * @return boolean
	 */
	private function _refresh_tests() {
		if ($this->updated_within(600)) {
			return false;
		}
		$this->_synchronize_with_disk();
		$this->tests_updated = Timestamp::now();
		$this->store();
		return true;
	}
	
	/**
	 * 
	 * @param array $where
	 */
	public function tests(array $where = array()) {
		$this->_refresh_tests();
		return $this->member_query("tests", $where);
	}
}
