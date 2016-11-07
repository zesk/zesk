-- Database: mysql
CREATE TABLE {table} (
	id			integer unsigned NOT NULL AUTO_INCREMENT,
	code	 	varchar(255),
	name 		varchar(255),
	body 		text,
	order_by	varchar(128) DEFAULT '0',
	created 	timestamp NOT NULL DEFAULT 0,
	modified 	timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	UNIQUE `code` (`code`)
);
