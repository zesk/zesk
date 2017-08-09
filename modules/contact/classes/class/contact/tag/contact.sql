create table `{table}` (
	`contact_tag` integer unsigned not null,
	`contact` integer unsigned not null,
	key contact_tag (`contact_tag`),
	key contact (`contact`)
);

