-- Database: mysql
CREATE TABLE {table} (
	`id`		integer unsigned not null auto_increment,
	`when`		timestamp not null default '0',
	`microsec`	integer unsigned not null default '0',
	`module`	varchar(64) not null,
	`message`	varchar(200) not null,
	`level`		tinyint not null,
	`level_string`		varchar(8) not null,
	`pid`		integer unsigned null,
	`server`	integer unsigned null,
	`ip`		integer unsigned null,
	`user`		integer unsigned null,
	`session`	integer unsigned null,
	`arguments`	text null,
	primary key (id),
	index when (`when`),
	index module (`module`),
	index level (`level`)
	index user (`user`),
	index ip (`ip`),
	index server (`server`),
	index pid (`pid`),
	index session (`session`)
);
