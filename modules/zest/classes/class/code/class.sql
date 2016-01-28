-- Database: SQLite3
CREATE TABLE {table} (
	id				integer unsigned NOT NULL PRIMARY KEY,
	name			varchar(128) NOT NULL DEFAULT '',
	source			integer unsigned NOT NULL,
);

CREATE UNIQUE INDEX {table}_name ON {table} (name);
