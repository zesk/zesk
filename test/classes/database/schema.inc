<?php
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class Test_Database_Schema extends Test_Unit {

	function test_0() {
		$object = new Test_Database_Schema_User();
		Database_Schema::updateObject($object);

		$objects = array();
		Database_Schema::updateObjectsCommand($objects);

		$path = null;
		$db_url = null;
		Database_Schema::updateObjectPath($path, $db_url);

		$object = null;
		Database_Schema::create($object);

		$db = null;
		$dbTableOld = null;
		$dbTableNew = null;
		$change_permanently = false;
		Database_Schema::update($db, $dbTableOld, $dbTableNew, $change_permanently);
	}
}
class Test_Database_Schema_User extends \zesk\User {
}

