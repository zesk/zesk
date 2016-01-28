-- Database: SQLite3
CREATE TABLE {table} (
	id				integer unsigned NOT NULL PRIMARY KEY,
	path			varchar(128) NOT NULL DEFAULT '',
	enabled			tinyint unsigned NOT NULL DEFAULT 0,
	file_md5		varchar(32) NULL,
	file_modtime	timestamp NULL,
	file_size		integer unsigned NULL,
	created			timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	parsed			timestamp NULL,
);

CREATE UNIQUE INDEX "{table}_path" ON "{table}" ( "path" );
