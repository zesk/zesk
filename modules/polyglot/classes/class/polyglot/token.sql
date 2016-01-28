CREATE TABLE `{table}` (
	id				integer unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
	language		char(2) NULL,
	dialect			char(2) NULL,
	md5				varbinary(16) NOT NULL,
	original		text,
	translation		text,
	context			varchar(64) NULL,
	user			integer unsigned NULL,
	status			varchar(16) NOT NULL,
	updated			timestamp NOT NULL DEFAULT 0,
	INDEX lang_dial (language,dialect,md5,user,context),
	INDEX user (`context`,`user`),
	INDEX `index_original` ( `original`(255) ),
	INDEX `index_translation` (`translation`(255))
);
