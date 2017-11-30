-- Database: mysql
CREATE TABLE `{table}` (
	`id` 				integer unsigned NOT NULL AUTO_INCREMENT,
	`hash`		 		varbinary(16) NOT NULL default '',
	`message_id`		varchar(128) NOT NULL default '',
	`mail_from` 		varchar(128) default '',
	`mail_to` 			varchar(128) default '',
	`subject` 			varchar(128) default NULL,
	`state` 			integer NOT NULL default '0',
	`date` 				datetime default NULL,
	`content_type` 		varchar(128) default '',
	`content` 			text,
	`size` 				integer default NULL,
	`user` 				integer unsigned default NULL,
	PRIMARY KEY  (`id`),
	KEY `mail_from` (`mail_from`),
	KEY `mail_to` (`mail_to`)
);

