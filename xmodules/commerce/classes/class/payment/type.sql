-- Database: mysql
CREATE TABLE `{table}` (
  `id` 				integer unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `code`	 		varchar(32) NOT NULL default '',
  `name` 			varchar(64) DEFAULT NULL,
  `description` 	text,
  `is_cc` 			tinyint unsigned NOT NULL DEFAULT 0,
  UNIQUE KEY `code` (`code`)
);

