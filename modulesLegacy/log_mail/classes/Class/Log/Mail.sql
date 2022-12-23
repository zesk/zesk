CREATE TABLE `{table}` (
	`id` integer unsigned AUTO_INCREMENT PRIMARY KEY NOT NULL,
	`session` integer unsigned NULL,
	`user` integer unsigned NULL,
	`code` varchar(128) NOT NULL DEFAULT '',
	`from` varchar(255) NOT NULL DEFAULT '',
	`to` text NOT NULL,
	`subject` varchar(255) NOT NULL DEFAULT '',
	`body` longtext DEFAULT NULL,
	`created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`sent` timestamp NULL,
	`type` varchar(16) DEFAULT NULL,
	`data` tinytext NOT NULL,
	UNIQUE KEY `code` (`code`),
	KEY `session` (`session`),
	KEY `user` (`user`),
	KEY `from` (`from`),
	KEY `created` (`created`),
	KEY `sent` (`sent`)
);

