-- Database: mysql
CREATE TABLE {table} (
	id 				integer unsigned NOT NULL AUTO_INCREMENT,
	parent			integer unsigned NULL,
	name			varchar(255),
	code			varchar(255),
	body			text,
	released		datetime NULL,
	created			datetime NOT NULL,
	modified		datetime NOT NULL,
	order_index		integer DEFAULT '0',
	image_path		varchar(128),
	active			tinyint unsigned NOT NULL DEFAULT 0,
	home			tinyint unsigned NOT NULL DEFAULT 0,
	objects			varchar(255),
	theme			varchar(255),
	layout			varchar(255),
	PRIMARY KEY (id),
	KEY order_index (`order_index`),
	KEY code (`code`),
	KEY parent (`parent`)
);
