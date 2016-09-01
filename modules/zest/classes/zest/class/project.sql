-- Database: SQLite3
CREATE TABLE {table} (
	id				integer unsigned NOT NULL PRIMARY KEY AUTOINCREMENT,
	path			text NOT NULL,
	created			DATETIME NOT NULL,
	modified		DATETIME NOT NULL,
	tests_updated	DATETIME NULL,
	executed		DATETIME NULL,
	first_success	DATETIME NULL,
	first_failure	DATETIME NULL,
	last_success	DATETIME NULL,
	last_failure	DATETIME NULL,
	stats_total		integer NOT NULL DEFAULT 0,
	stats_success	integer NOT NULL DEFAULT 0,
	stats_failure	integer NOT NULL DEFAULT 0,
	status			tinyint NOT NULL DEFAULT 0,
);

CREATE INDEX path ON {table} ( path );
