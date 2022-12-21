<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Class_DBSchemaTest4 extends Class_Base {
	public function initialize(): void {
		parent::initialize();
		$this->table = DBSchemaTest4::$test_table;
	}

	public array $column_types = [
		'ID' => self::type_id,
		'Depth' => self::type_integer,
		'CodeName' => self::type_string,
		'Name' => self::type_string,
	];
}
class DBSchemaTest4 extends ORMBase {
	public static $test_table = '';

	public static $test_table2 = '';

	public function schema(): ORM_Schema|array|string|null {
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
class Class_DBSchemaTest5 extends Class_Base {
	public function initialize(): void {
		parent::initialize();
		$this->table = DBSchemaTest5::$test_table;
	}

	public array $column_types = [
		'ID' => self::type_id,
		'Hash' => self::type_string,
		'Phrase' => self::type_string,
		'Created' => self::type_created,
		'Modified' => self::type_modified,
		'Status' => self::type_integer,
		'IsOrganic' => self::type_string,
		'LastUsed' => self::type_timestamp,
	];
}
class DBSchemaTest5 extends ORMBase {
	public static $test_table = null;

	public function schema(): string|array|null|ORM_Schema {
		return 'CREATE TABLE `{table}` (
		`ID` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
		`Hash` binary(16) NOT NULL,
		`Phrase` varchar(255) NOT NULL,
		`Created` timestamp NOT NULL DEFAULT 0,
		`Modified` timestamp NOT NULL DEFAULT 0,
		`Status` smallint(1) DEFAULT \'0\',
		`IsOrganic` enum(\'false\',\'true\') DEFAULT \'false\',
		`LastUsed` timestamp NOT NULL DEFAULT 0,
		UNIQUE Hash (Hash) USING HASH,
		INDEX created ( Created ),
		INDEX phrase ( Phrase(64) )
		);';
	}
}
class Class_DBSchemaTest6 extends Class_Base {
	public array $column_types = [
		'ID' => self::type_id,
		'Hash' => self::type_string,
		'Protocol' => self::type_string,
		'Proto' => self::type_object,
		'Domain' => self::type_object,
		'Port' => self::type_integer,
		'URI' => self::type_object,
		'QueryString' => self::type_object,
		'Title' => self::type_object,
		'Fragment' => self::type_string,
		'Frag' => self::type_object,
	];

	public function initialize(): void {
		parent::initialize();
		$this->table = DBSchemaTest6::$test_table;
	}
}
class DBSchemaTest6 extends ORMBase {
	public static $test_table = null;

	public function schema(): string|array|null|ORM_Schema {
		return 'CREATE TABLE `{table}` (
		`ID` int(11) unsigned NOT NULL auto_increment,
		`Hash` char(32) NOT NULL,
		`Protocol` varchar(7) NOT NULL default \'\',
		`Domain` int(11) unsigned default NULL,
		`Port` smallint(11) unsigned NULL,
		`URI` int(11) unsigned default NULL,
		`QueryString` int(11) unsigned default NULL,
		`Title` int(11) unsigned NULL,
		`Fragment` text,
		`Frag` int(11) unsigned NULL,
		PRIMARY KEY  (`ID`),
		UNIQUE KEY `Hash` (`Hash`) USING HASH,
		KEY `domain` (`Domain`),
		KEY `title` (`Title`)
		);';
	}
}
class Class_DBSchemaTest7 extends Class_Base {
	public array $column_types = [
		'ID' => self::type_id,
		'Hash' => self::type_string,
		'Protocol' => self::type_string,
		'Proto' => self::type_object,
		'Domain' => self::type_object,
		'Port' => self::type_integer,
		'URI' => self::type_object,
		'QueryString' => self::type_object,
		'Title' => self::type_object,
		'Fragment' => self::type_string,
		'Frag' => self::type_object,
	];

	public function initialize(): void {
		parent::initialize();
		$this->table = DBSchemaTest7::$test_table;
	}
}
class DBSchemaTest7 extends ORMBase {
	public static $test_table = null;

	public function schema(): string|array|null|ORM_Schema {
		return 'CREATE TABLE `{table}` (
	`ID` int(11) unsigned NOT NULL auto_increment,
	`Hash` char(32) NOT NULL,
	`Protocol` varchar(7) NOT NULL default \'\',
	`Proto` tinyint NOT NULL default \'0\',
	`Domain` int(11) unsigned default NULL,
	`Port` smallint(11) unsigned NULL,
	`URI` int(11) unsigned default NULL,
	`QueryString` int(11) unsigned default NULL,
	`Title` int(11) unsigned NULL,
	`Fragment` text,
	`Frag` int(11) unsigned NULL,
	PRIMARY KEY  (`ID`),
	UNIQUE KEY `Hash` (`Hash`) USING HASH,
	KEY `domain` (`Domain`),
	KEY `title` (`Title`)
	);';
	}
}
class Class_DBSchemaTest8 extends Class_Base {
	public array $column_types = [
		'ID' => self::type_id,
		'Hash' => self::type_string,
		'Size' => self::type_integer,
	];

	public function initialize(): void {
		parent::initialize();
		$this->table = DBSchemaTest8::$test_table;
	}
}
class DBSchemaTest8 extends ORMBase {
	public static $test_table = null;

	public function schema(): string|array|null|ORM_Schema {
		return 'CREATE TABLE `{table}` (
			`ID` int(11) unsigned NOT NULL auto_increment,
			`Hash` char(32) NOT NULL,
			`Size` bigint unsigned NOT NULL,
			PRIMARY KEY (ID)
		);';
	}
}
class Class_DBSchemaTest_columns_0 extends Class_Base {
	public array $column_types = [
		'ID' => self::type_id,
		'Hash' => self::type_string,
		'Protocol' => self::type_string,
		'Domain' => self::type_object,
		'Port' => self::type_integer,
		'URI' => self::type_object,
		'QueryString' => self::type_object,
		'Fragment' => self::type_string,
		'Frag' => self::type_object,
	];
}
class DBSchemaTest_columns_0 extends ORMBase {
	public function schema(): string|array|null|ORM_Schema {
		return 'CREATE TABLE `{table}` (
					`ID` int(11) unsigned NOT NULL auto_increment,
					`Hash` char(32) NOT NULL,
					`Protocol` varchar(7) NOT NULL default \'\',
					`Domain` int(11) unsigned default NULL,
					`Port` smallint(11) unsigned NULL,
					`URI` int(11) unsigned default NULL,
					`QueryString` int(11) unsigned default NULL,
					`Title` int(11) unsigned NULL,
					`Fragment` text,
					`Frag` int(11) unsigned NULL,
					PRIMARY KEY  (`ID`),
					UNIQUE KEY `Hash` (`Hash`) USING HASH,
					KEY `domain` (`Domain`),
					KEY `title` (`Title`)
				);';
	}
}
class Class_DBSchemaTest_columns_1 extends Class_Base {
	public array $column_types = [
		'ID' => self::type_id,
		'Hash' => self::type_string,
		'Protocol' => self::type_string,
		'Domain' => self::type_object,
		'Port' => self::type_integer,
		'URI' => self::type_object,
	];
}
class DBSchemaTest_columns_1 extends ORMBase {
	public function schema(): string|array|null|ORM_Schema {
		return 'CREATE TABLE `{table}` (
					`ID` int(11) unsigned NOT NULL auto_increment,
					`Hash` char(32) NOT NULL,
					`Protocol` varchar(7) NOT NULL default \'\',
					`Domain` int(11) unsigned default NULL,
					`Port` smallint(11) unsigned NULL,
					`URI` int(11) unsigned default NULL
				);';
	}
}
