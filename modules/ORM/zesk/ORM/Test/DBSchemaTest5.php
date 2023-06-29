<?php
declare(strict_types=1);

namespace zesk\ORM\Test;

use zesk\ORM\ORMBase;
use zesk\ORM\Schema;

class DBSchemaTest5 extends ORMBase
{
	public static string $test_table = '';

	public function schema(): string|array|null|Schema
	{
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
