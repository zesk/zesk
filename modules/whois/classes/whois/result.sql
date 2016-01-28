-- Database: mysql
CREATE TABLE {table} (
	ID					integer unsigned PRIMARY KEY AUTO_INCREMENT NOT NULL,
	TLD					varchar(16) NOT NULL,
	Host				varchar(96) NOT NULL,
	UNIQUE TLD (TLD)
);
