CREATE TABLE `{table}` (
	`id`			integer unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
	`scope`			varchar(128) NOT NULL,
	`user`			integer unsigned NULL,
	`name`			varchar(128) NOT NULL,
	`filters`		text NOT NULL,
	INDEX sun (`scope`,`user`,`name`)
);