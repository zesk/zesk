-- Database: mysql
-- COLUMN: User -> user
-- COLUMN: Role -> role
CREATE TABLE {table} (
	user			integer unsigned NOT NULL,
	role			integer unsigned NOT NULL,
	created 		timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	creator			integer unsigned NULL,
	UNIQUE ur (user,role),
	UNIQUE ru (role,user)
);

