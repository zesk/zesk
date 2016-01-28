-- Database: mysql
CREATE TABLE `{table}` (
	ID					integer unsigned NOT NULL AUTO_INCREMENT,
	Content_Tag_Type	integer unsigned NOT NULL,
	Parent				integer unsigned NULL,
	Name				varchar(80) NOT NULL,
	Description			longtext NOT NULL DEFAULT '',
	Created				timestamp NOT NULL DEFAULT 0,
	Modified			timestamp NOT NULL DEFAULT 0,
	INDEX `Content_Tag_Group` ( `Content_Tag_Group` ),
	INDEX `Name` ( `Name` ),
	PRIMARY KEY ( `ID ` )
);
