-- Database: mysql
CREATE TABLE {table} (
	id					integer unsigned PRIMARY KEY AUTO_INCREMENT NOT NULL,
	contact				integer unsigned NOT NULL,
	type				varchar(8),
	result				integer unsigned NOT NULL,
	KEY contact (contact),
	KEY type (type),
	KEY result (result)
);
