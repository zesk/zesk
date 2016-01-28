-- Database: SQLite3
CREATE TABLE {table} (
	id			integer unsigned PRIMARY KEY,
	name		varchar(64) NOT NULL,
	path		varchar(128) NOT NULL,
	class		varchar(128) NOT NULL,
	created		timestamp NOT NULL,
	last_run	timestamp NULL,
	last_status tinyint NOT NULL DEFAULT 0
);
