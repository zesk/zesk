-- Database: mysql
CREATE TABLE {table} (
	id					integer unsigned PRIMARY KEY AUTO_INCREMENT NOT NULL,
	name				varchar(64) NOT NULL,
	server				integer unsigned NOT NULL,
	registrar			integer NULL,
	created				timestamp NOT NULL DEFAULT 0,
	fetched				timestamp NULL DEFAULT NULL,
	results				text,
	taken				tinyint NOT NULL DEFAULT 0,
);
