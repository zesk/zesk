-- Database: mysql
-- placement: top | bottom | left | right | auto
CREATE TABLE `{table}` (
	id				integer unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
	target			varchar(128) NOT NULL,
	`type`			varchar(32) NOT NULL,	
	`placement`		varchar(32) NOT NULL, 	
	title			varchar(128) NOT NULL,
	content			text NOT NULL DEFAULT '',
	map				blob NULL,
	content_wraps	blob NULL,
	content_url		text NOT NULL DEFAULT '',
	require_user	tinyint NOT NULL DEFAULT '0',
	active			tinyint NOT NULL DEFAULT '0',
	created			timestamp NOT NULL default 0,
	modified		timestamp NOT NULL default 0,
	show_first		timestamp NULL,
	show_recent		timestamp NULL,
	show_count		integer unsigned NOT NULL DEFAULT 0,
	UNIQUE tt (`target`),
	INDEX mod (`modified`)
);
