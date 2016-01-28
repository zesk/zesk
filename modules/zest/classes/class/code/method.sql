-- Database: SQLite3
CREATE TABLE {table} (
	id				integer unsigned NOT NULL PRIMARY KEY,
	class			integer unsigned NOT NULL,
	method			varchar(128) NOT NULL DEFAULT '',
	arguments		text NOT NULL,
	test			integer unsigned NOT NULL
);

CREATE UNIQUE INDEX {table}_class_method ON {table} (class,method);
