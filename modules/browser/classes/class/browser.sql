-- Database: MySQL
CREATE TABLE `{table}` (
	id	 			integer unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
	md5				varbinary(16) NOT NULL,
	is_phone		tinyint NOT NULL DEFAULT 0,
	is_tablet		tinyint NOT NULL DEFAULT 0,
	is_desktop		tinyint NOT NULL DEFAULT 0,
	tags			blob NOT NULL,
	name			text NOT NULL,
	created			timestamp NOT NULL DEFAULT 0,
	parsed			timestamp NOT NULL DEFAULT 0,
	UNIQUE md5 (`md5`),
	INDEX is_phone (`is_phone`),
	INDEX is_tablet (`is_tablet`),
	INDEX is_desktop (`is_desktop`)
);
