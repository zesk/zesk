CREATE TABLE `{table}` (
	`id`			integer unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,

	`date`			date NOT NULL,
	`hash`			varbinary(16) NOT NULL,
	
	`server`		integer unsigned NULL,
	`application`	varchar(64) NOT NULL,
	`context`		varchar(62) NULL,
	`type`			varchar(24),
	`message`		text NOT NULL DEFAULT '',
	`fatal`			tinyint unsigned NOT NULL DEFAULT 0,

	`first`			timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
	`first_msec`	smallint NOT NULL DEFAULT 0,
	`recent`		timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
	`recent_msec`	smallint NOT NULL DEFAULT 0,
	
	`total`			integer unsigned NOT NULL DEFAULT 0,
	
	UNIQUE `date_hash` (`date`, `hash`)
	INDEX `first` (`first`)
	INDEX `recent` (`recent`)
);
