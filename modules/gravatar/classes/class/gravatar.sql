CREATE TABLE `{table}` (
	id integer unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
	email varchar(64),
	status tinyint NOT NULL DEFAULT 0,
	username varchar(128),
	display_name varchar(128),
	url_profile varchar(128),
	url_thumbnail varchar(128),
	last_result text,
	created timestamp NOT NULL DEFAULT 0,
	updated timestamp NOT NULL DEFAULT 0
);
