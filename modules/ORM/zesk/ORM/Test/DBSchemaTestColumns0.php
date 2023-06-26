<?php
declare(strict_types=1);

namespace zesk\ORM\Test;

use zesk\ORM\ORMBase;
use zesk\ORM\Schema;

class DBSchemaTestColumns0 extends ORMBase {
	public function schema(): string|array|null|Schema {
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
