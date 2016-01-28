-- Database: mysql
CREATE TABLE {table} (
	ID 				integer unsigned NOT NULL AUTO_INCREMENT,
	Parent			integer NULL,
	Name			varchar(255),
	CodeName		varchar(255),
	Body			text,
	Released		datetime NULL,
	Created			datetime NOT NULL,
	Modified		datetime NOT NULL,
	OrderIndex		integer DEFAULT '0',
	ImagePath		varchar(128),
	IsActive		enum('false','true') DEFAULT 'false',
	IsHome			enum('false','true') DEFAULT 'false',
	ContentObjects	varchar(255),
	ContentTemplate	varchar(255),
	ContentLayout	varchar(255),
	PRIMARY KEY (ID),
	KEY order_index (`OrderIndex`),
	KEY code (`CodeName`),
	KEY parent (`Parent`)
);

