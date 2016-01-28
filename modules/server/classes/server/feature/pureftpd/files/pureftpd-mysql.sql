-- Database: mysql
CREATE TABLE `{table}` (
  `name` varchar(16) NOT NULL,
  `password` varchar(64) NOT NULL,
  `uid` int(11) NOT NULL DEFAULT '-1',
  `gid` int(11) NOT NULL DEFAULT '-1',
  `dir` varchar(128) NOT NULL,
  `quota_files` int(11) DEFAULT NULL,
  `quota_size` int(11) DEFAULT NULL,
  `speed_up` double DEFAULT NULL,
  `speed_down` double DEFAULT NULL,
  PRIMARY KEY (`name`)
);
