<?php

abstract class Repository extends Hookable {

	public static function factory($name) {
		try {
			return zesk::factory("Repository_$name");
		} catch (Exception_Class_NotFound $e) {
			return null;
		}
	}

	/**
	 * Check a target prior to updating it
	 *
	 * @param string $target
	 * @return boolean True if update should continue
	 */
	abstract function pre_update($target);

	abstract function rollback($target);

	abstract function post_update($target);
}