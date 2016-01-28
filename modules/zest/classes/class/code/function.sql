-- Database: SQLite3
CREATE TABLE {table} (
	id				integer unsigned NOT NULL PRIMARY KEY,
	source			integer unsigned NOT NULL,
	function		varchar(128) NOT NULL DEFAULT '',
	arguments		text NOT NULL,
	test			integer unsigned NOT NULL
);

CREATE UNIQUE INDEX {table}_func ON {table} (function);
