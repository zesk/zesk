-- Database: mysql
-- COLUMN: ID -> id
-- COLUMN: Contact -> contact
-- COLUMN: Label -> label
-- COLUMN: Prefix -> name_first
-- COLUMN: FirstName -> name_first
-- COLUMN: MiddleName -> name_middle
-- COLUMN: LastName -> name_last
-- COLUMN: Nickname -> name_nick
-- COLUMN: Suffix -> name_suffix
-- COLUMN: MaidenName -> name_maiden
-- COLUMN: Title -> title
-- COLUMN: Company -> company
-- COLUMN: Gender -> gender
-- COLUMN: Spouse -> spouse
-- COLUMN: Children -> children
-- COLUMN: Created -> created
-- COLUMN: Modified -> modified
CREATE TABLE `{table}` (
	`id` integer unsigned not null auto_increment,
	`contact` integer unsigned not null,
	`label` integer unsigned null,
	`name_prefix` varchar(32) not null default '',
	`name_first` varchar(64) not null default '',
	`name_middle` varchar(64) not null default '',
	`name_last` varchar(64) not null default '',
	`name_suffix` varchar(32) not null default '',
	`name_nick` varchar(64) not null default '',
	`name_maiden` varchar(64) not null default '',
	`title` varchar(64) not null default '',
	`company` varchar(64) not null default '',
	`gender` tinyint not null default 0,
	`spouse` varchar(64) not null default '',
	`children` text not null default '',
	`created` timestamp not null default CURRENT_TIMESTAMP,
	`modified` timestamp not null,
	primary key  (`id`),
	key contact (`contact`)
);

