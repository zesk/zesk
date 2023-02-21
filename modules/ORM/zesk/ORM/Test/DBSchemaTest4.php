<?php
declare(strict_types=1);

namespace zesk\ORM\Test;

use zesk\ORM\ORMBase;
use zesk\ORM\Schema;

class DBSchemaTest4 extends ORMBase {
	public static $test_table = '';

	public static $test_table2 = '';

	public function schema(): Schema|array|string|null {
		$table = self::$test_table;
		$table2 = self::$test_table2;
		return "CREATE TABLE `$table` (
		`ID` int(11) unsigned NOT NULL auto_increment,
		`Depth` tinyint(4) default '0',
		`CodeName` varbinary(80) NOT NULL default '',
		`Name` varchar(128) NOT NULL default '',
		PRIMARY KEY  (`ID`),
		UNIQUE KEY `codename` (`Depth`,`CodeName`)
		);

		CREATE TABLE `$table2` (
		`ID` int(11) unsigned NOT NULL auto_increment,
		`Depth` tinyint(4) default '0',
		`CodeName` varbinary(80) NOT NULL default '',
		`Name` varchar(128) NOT NULL default '',
		PRIMARY KEY  (`ID`),
		UNIQUE KEY `codename` (`Depth`,`CodeName`)
		);
		INSERT INTO $table ( Depth, CodeName, Name ) VALUES ( 3, 'foo', 'bar');";
	}
}
