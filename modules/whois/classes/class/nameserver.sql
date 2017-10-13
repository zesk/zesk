-- Database: mysql
CREATE TABLE {table} (
	id					integer unsigned PRIMARY KEY AUTO_INCREMENT NOT NULL,
	domain				varchar(64) NOT NULL,
	created				timestamp NOT NULL,
	first_used			timestamp NULL,
	last_used			timestamp NULL,
	KEY d (Domain)
);
