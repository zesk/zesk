-- Database: mysql
CREATE TABLE `{table}` (
	ID integer unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
	FQDN varchar(128) NOT NULL,
	IP integer unsigned NOT NULL,
	IP6 varchar(16) NULL,
	Domain integer unsigned NULL
	INDEX d (Domain),
	INDEX f (FQDN)
);

