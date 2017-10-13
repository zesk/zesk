-- Database: mysql
CREATE TABLE {table} (
	id					integer unsigned PRIMARY KEY AUTO_INCREMENT NOT NULL,
	host				varchar(96) NOT NULL,
	created				timestamp NOT NULL,
	used				timestamp NOT NULL,
	UNIQUE h (host)
);
