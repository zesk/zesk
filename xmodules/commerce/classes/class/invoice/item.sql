-- Database: mysql
CREATE TABLE `{table}` (
	`id`				integer unsigned NOT NULL AUTO_INCREMENT,
	`invoice`			integer unsigned NOT NULL,
	`order_index`		integer NOT NULL,
	`object`			integer DEFAULT NULL,
	`product`			integer unsigned DEFAULT NULL,
	`transaction`		integer unsigned DEFAULT NULL,
	`item_type`			integer unsigned NOT NULL,
	`description`		text,
	`quantity`			decimal(12,2) DEFAULT '1.00',
	`tax`				integer unsigned NOT NULL,
	`total`				integer unsigned NOT NULL,
	PRIMARY KEY (`id`),
	INDEX `invoice` (`invoice`),
	INDEX `prod` (`product`),
	INDEX `transaction` (`transaction`),
	INDEX `item_type` (`item_type`),
	UNIQUE KEY `u` (`invoice`,`order_index`,`object`,`product`,`transaction`)
) ENGINE=InnoDB;
