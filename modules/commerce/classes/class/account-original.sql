-- Database: mysql
-- RENAME: ID                     -> id
-- RENAME: CodeName               -> code
-- RENAME: Name                   -> name
-- RENAME: Description            -> description
-- RENAME: AccountType            -> account_type
-- RENAME: IsBillable             -> is_billable
-- RENAME: Email                  -> email
-- RENAME: Commission             -> commission
-- RENAME: NextBillDate           -> next_bill_date
-- RENAME: Notes                  -> notes
-- RENAME: Referrer               -> referrer
-- RENAME: Company                -> company
-- RENAME: BillAddress            -> billing_address
-- RENAME: Payment                -> preferred_payment
-- RENAME: IsReferrer             -> is_referrer
-- RENAME: Created                -> created
-- RENAME: Modified               -> modified
-- RENAME: ActivateDateTime       -> activated
-- RENAME: ExpireDateTime         -> expire
-- RENAME: Cancelled              -> cancelled
-- RENAME: Deleted                -> deleted
-- RENAME: ManagerAccount         -> manager_account
-- RENAME: Currency               -> currency
-- RENAME: PaymentAccount         -> payment_account
-- RENAME: Checked                -> checked
-- RENAME: Balance                -> balance
-- RENAME: NewBalance             -> cleared_balance
-- RENAME: BillTimeUnit           -> bill_unit
-- RENAME: BillTimeUnitCount      -> bill_unit_count
-- RENAME: CancelledRequest       -> cancel_request
-- RENAME: IsFree                 -> is_free
-- RENAME: CreatedBy              -> created_by
-- RENAME: IsDemo                 -> is_demo
-- RENAME: BrandImage             -> brand_image
-- RENAME: BrandSlogan            -> brand_slogan
-- RENAME: BrandStyle             -> brand_style
-- RENAME: Advertise              -> advertise
-- RENAME: BrandImageHash         -> brand_image_hash
CREATE TABLE `{table}` (
	id                    	 integer unsigned NOT NULL auto_increment,
	code                  	 varchar(32) NOT NULL default '',
	name                  	 varchar(64) default NULL,
	description           	 text,
	account_type          	 integer unsigned default NULL,
	is_billable           	 enum('false','true') default NULL,
	email                 	 varchar(64) default NULL,
	commission            	 integer unsigned default NULL,
	next_bill_date        	 datetime default NULL,
	notes                 	 text,
	referrer              	 integer unsigned default NULL,
	company               	 integer unsigned default NULL,
	billing_address       	 integer unsigned default NULL,
	preferred_payment     	 integer unsigned default NULL,
	is_referrer           	 enum('false','true') default NULL,
	created               	 datetime default NULL,
	modified              	 datetime default NULL,
	activated             	 datetime default NULL,
	expire                	 datetime default NULL,
	cancelled             	 datetime default NULL,
	deleted               	 datetime default NULL,
	manager_account       	 integer unsigned default NULL,
	currency              	 integer unsigned default NULL,
	payment_account       	 integer unsigned default NULL,
	checked               	 datetime default NULL,
	balance               	 decimal(12,2) default NULL,
	cleared_balance       	 decimal(12,2) default NULL,
	bill_unit             	 varchar(8) default NULL,
	bill_unit_count       	 smallint(6) default NULL,
	cancel_request        	 datetime default NULL,
	is_free               	 enum('false','true') default 'false',
	created_by            	 integer unsigned default NULL,
	is_demo               	 enum('false','true') NOT NULL default 'false',
	brand_image           	 varchar(128) default NULL,
	brand_slogan          	 varchar(128) default NULL,
	brand_style           	 text,
	advertise             	 enum('false','true') default 'false',
	brand_image_hash      	 varchar(40) default NULL,
	PRIMARY KEY  (`id`),
	UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB;