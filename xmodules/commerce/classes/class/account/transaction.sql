CREATE TABLE `{table}` (
  `id`					integer unsigned NOT NULL AUTO_INCREMENT,
  `transaction_group`	integer NOT NULL,
  `description` 		text,
  `account` 			integer unsigned NOT NULL,
  `transaction_type` 	integer unsigned NOT NULL,
  `when`	 			timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created` 			timestamp NOT NULL DEFAULT 0,
  `modified` 			timestamp NOT NULL DEFAULT 0,
  `invoice` 			integer unsigned NULL,
  `amount` 				decimal(12,2) NOT NULL default '0.00',
  `reconciled` 			tinyint unsigned NOT NULL DEFAULT 0,
  `payment_transaction`	integer unsigned default NULL,
  `order_index`			integer NOT NULL,
  `invoice_item` 		integer unsigned default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `xaction` (`transaction_group`,`account`,`transaction_type`),
  KEY `balance` (`account`,`when`,`order_index`,`amount`),
  KEY `invoice` (`invoice`)
) ENGINE=InnoDB;

