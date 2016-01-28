-- Database: mysql
CREATE TABLE {table} (
	ID					integer unsigned PRIMARY KEY AUTO_INCREMENT NOT NULL,
	Name				varchar(96) NOT NULL,
	UNIQUE Name (Name)
);
