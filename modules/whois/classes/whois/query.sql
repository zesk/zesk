-- Database: mysql
CREATE TABLE {table} (
	ID					integer unsigned PRIMARY KEY AUTO_INCREMENT NOT NULL,
	Name				varchar(64) NOT NULL,
	Whois_Server		integer unsigned NOT NULL,
	Whois_Registrar		integer NULL,
	Created				timestamp NOT NULL DEFAULT 0,
	Fetched				timestamp NULL DEFAULT NULL,
	Results				text,
	Taken				enum('false','true') NOT NULL DEFAULT 'false',
	UNIQUE TLD (TLD)
);
