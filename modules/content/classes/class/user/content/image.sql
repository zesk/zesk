CREATE TABLE `{table}` (
	`user` integer unsigned NOT NULL,
	`image` integer unsigned NOT NULL,
	PRIMARY KEY (`user`,`image`),
	UNIQUE iu (`image`,`user`)
);