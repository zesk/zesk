-- Database: mysql
-- COLUMN: IP -> ip
-- COLUMN: UTC -> utc
-- COLUMN: Tag -> tag
CREATE TABLE `{table}` (
	ip		integer unsigned NOT NULL,
	utc		timestamp not NULL DEFAULT 0,
	tag		integer unsigned NOT NULL,
	PRIMARY KEY (ip, utc, tag),
	INDEX ip (ip),
	INDEX UTC (utc),
	INDEX Tag (tag)
);
