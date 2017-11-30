-- Database: mysql
-- RENAME: ID -> id
-- RENAME: Created -> created
-- RENAME: PaymentTransaction -> payment_transaction
-- RENAME: Amount -> amount
CREATE TABLE `{table}` (
	`id` integer unsigned NOT NULL AUTO_INCREMENT,
	`created` datetime DEFAULT NULL,
	`invoice` integer unsigned DEFAULT NULL,
	`payment_transaction` integer unsigned DEFAULT NULL,
	`amount` decimal(12,2) NOT NULL DEFAULT '0.00',
	PRIMARY KEY (`id`),
	KEY `invoice` (`invoice`),
	KEY `PT` (`payment_transaction`)
) ENGINE=InnoDB;
