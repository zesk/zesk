-- Database: mysql
-- COLUMN: Path -> path
-- COLUMN: Handler -> handler
-- COLUMN: State -> state
-- COLUMN: Created -> created
-- COLUMN: Modified -> modified
CREATE TABLE {table} (
	id integer unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
	server integer unsigned NOT NULL,
	path varchar(128) NOT NULL,
	handler varchar(32) NOT NULL,
	state text NOT NULL,
	created timestamp NOT NULL DEFAULT 0,
	modified timestamp NOT NULL DEFAULT 0,
	UNIQUE server_path (server, path),
	INDEX path (path)
);
