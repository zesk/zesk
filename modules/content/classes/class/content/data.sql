-- Database: mysql
-- COLUMN: ID -> id
-- COLUMN: Size -> size
-- COLUMN: MD5Hash -> md5hash
-- COLUMN: Type -> type
-- COLUMN: Data -> data
-- COLUMN: Checked -> checked
-- COLUMN: Missing -> missing
CREATE TABLE `{table}` (
	`id`			integer unsigned not null primary key auto_increment,
	`size`			bigint unsigned not null,
	`md5hash`		varbinary(16) not null,
	`type`			enum('data','path'),
	`data`			longblob null,
	`checked`		timestamp null,
	`missing`		timestamp null,
	unique by_hash (`md5hash`),
	index by_size (`size`)
);
