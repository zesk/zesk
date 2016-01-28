-- Database: mysql
-- COLUMN: ID -> id
-- COLUMN: CodeName -> code
-- COLUMN: Name -> name
-- COLUMN: IsRoot -> is_root
-- COLUMN: IsDefault -> is_default
CREATE TABLE `{table}` (
	`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
	`code` varchar(32) NOT NULL DEFAULT '',
	`name` varchar(64) NOT NULL DEFAULT '',
	`is_root` bit(1) NOT NULL DEFAULT 0,
	`is_default` bit(1) NOT NULL DEFAULT 0,
	PRIMARY KEY (`id`),
	UNIQUE KEY `code` (`code`),
	INDEX `is_default` (`is_default`)
);

INSERT INTO `{table}` (id,code,name,is_root,is_default) VALUES (1,'root','Administrator',1,0);
INSERT INTO `{table}` (id,code,name,is_root,is_default) VALUES (2,'guest','Guest',0,1);
