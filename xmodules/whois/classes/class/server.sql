-- Database: mysql
CREATE TABLE {table} (
	id					integer unsigned PRIMARY KEY AUTO_INCREMENT NOT NULL,
	tld					varchar(16) NOT NULL,
	host				varchar(96) NOT NULL,
	UNIQUE tld (tld)
);

INSERT INTO `{table}` ( id, tld, host ) VALUES ( 1, '.', 'whois.iana.org' );
