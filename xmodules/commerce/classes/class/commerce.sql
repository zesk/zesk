-- Database: mysql
CREATE TABLE `TInvoiceItem` (
  `ID` integer unsigned NOT NULL auto_increment,
  `Invoice` integer unsigned NOT NULL,
  `ListOrder` integer NOT NULL,
  `Object` integer default NULL,
  `Product` integer unsigned default NULL,
  `AccountTransaction` integer unsigned default NULL,
  `ItemType` integer unsigned NOT NULL,
  `Description` text,
  `Quantity` decimal(12,2) default '1.00',
  `Tax` integer unsigned NOT NULL,
  `Total` integer unsigned NOT NULL,
  `IsNew` enum('false','true') NOT NULL default 'true',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `u` (`Invoice`,`ListOrder`,`Object`,`Product`,`AccountTransaction`)
) ENGINE=InnoDB
;
CREATE TABLE `TProduct` (
  `ID` integer unsigned NOT NULL auto_increment,
  `CodeName` varchar(32) NOT NULL default '',
  `Name` varchar(252) default NULL,
  `Description` text,
  `HasCommission` enum('false','true') default 'false',
  `Site` integer unsigned default NULL,
  `ModelType` text,
  `PricingClass` varchar(64) default NULL,
  PRIMARY KEY  (`ID`),
  KEY `CodeName` (`CodeName`)
) ENGINE=InnoDB
;
CREATE TABLE `TInvoiceItemType` (
  `ID` integer unsigned NOT NULL auto_increment,
  `CodeName` varchar(32) default NULL,
  `OrderIndex` integer default NULL,
  `Name` varchar(252) default NULL,
  `Cost` decimal(13,6) default NULL,
  `QuantityType` varchar(32) NOT NULL default '',
  `QuantityStart` integer default NULL,
  `QuantityEnd` integer default NULL,
  `QuantityMin` integer default NULL,
  `QuantityMax` integer default NULL,
  `ObjectClass` varchar(128) default NULL,
  `HasCommission` enum('false','true') default 'false',
  `Description` text,
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `u` (`CodeName`)
) ENGINE=InnoDB
;
CREATE TABLE `TAccount` (
  `ID` integer unsigned NOT NULL auto_increment,
  `CodeName` varchar(32) NOT NULL default '',
  `Name` varchar(64) default NULL,
  `Description` text,
  `AccountType` integer unsigned default NULL,
  `IsBillable` enum('false','true') default NULL,
  `Email` varchar(64) default NULL,
  `Commission` integer unsigned default NULL,
  `NextBillDate` datetime default NULL,
  `Notes` text,
  `Referrer` integer unsigned default NULL,
  `Company` integer unsigned default NULL,
  `BillAddress` integer unsigned default NULL,
  `Payment` integer unsigned default NULL,
  `IsReferrer` enum('false','true') default NULL,
  `Created` datetime default NULL,
  `Modified` datetime default NULL,
  `ActivateDateTime` datetime default NULL,
  `ExpireDateTime` datetime default NULL,
  `Cancelled` datetime default NULL,
  `Deleted` datetime default NULL,
  `ManagerAccount` integer unsigned default NULL,
  `Currency` integer unsigned default NULL,
  `PaymentAccount` integer unsigned default NULL,
  `Checked` datetime default NULL,
  `Balance` decimal(12,2) default NULL,
  `NewBalance` decimal(12,2) default NULL,
  `BillTimeUnit` varchar(8) default NULL,
  `BillTimeUnitCount` smallint(6) default NULL,
  `CancelledRequest` datetime default NULL,
  `IsFree` enum('false','true') default 'false',
  `CreatedBy` integer unsigned default NULL,
  `IsDemo` enum('false','true') NOT NULL default 'false',
  `BrandImage` varchar(128) default NULL,
  `BrandSlogan` varchar(128) default NULL,
  `BrandStyle` text,
  `Advertise` enum('false','true') default 'false',
  `BrandImageHash` varchar(40) default NULL,
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `CodeName` (`CodeName`)
) ENGINE=InnoDB
;
CREATE TABLE `TAccountType` (
  `ID` integer unsigned NOT NULL auto_increment,
  `CodeName` varchar(32) NOT NULL default '',
  `Name` varchar(64) default NULL,
  `Description` text,
  `DefaultCommission` integer unsigned default NULL,
  `ReferrerCommission` integer unsigned default NULL,
  `IsBillable` enum('false','true') default 'true',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `CodeName` (`CodeName`)
) ENGINE=InnoDB
;
CREATE TABLE `TAccountTransaction` (
  `ID` integer unsigned NOT NULL auto_increment,
  `TransactionGroup` integer NOT NULL,
  `Description` text,
  `Account` integer unsigned NOT NULL,
  `TransactionType` integer unsigned NOT NULL,
  `EffectiveDate` datetime NOT NULL default '0000-00-00 00:00:00',
  `Created` datetime default NULL,
  `Modified` datetime default NULL,
  `Invoice` integer unsigned default NULL,
  `Amount` decimal(12,2) NOT NULL default '0.00',
  `Reconciled` enum('false','true') default 'false',
  `PaymentTransaction` integer unsigned default NULL,
  `OrderIndex` integer NOT NULL,
  `InvoiceItem` integer unsigned default NULL,
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `xaction` (`TransactionGroup`,`Account`,`TransactionType`),
  KEY `balance` (`Account`,`EffectiveDate`,`OrderIndex`,`Amount`),
  KEY `invoice` (`Invoice`)
) ENGINE=InnoDB
;
CREATE TABLE `TPayment` (
  `ID` integer unsigned NOT NULL auto_increment,
  `Name` varchar(64) default NULL,
  `Description` text,
  `PaymentType` integer unsigned NOT NULL,
  `OwnerName` varchar(64) NOT NULL default '',
  `AccountNumber` varchar(32) NOT NULL default '',
  `SecurityCode` varchar(8) default NULL,
  `Address` integer unsigned default NULL,
  `ExpireDate` datetime NOT NULL default '0000-00-00 00:00:00',
  `Created` datetime default NULL,
  `Modified` datetime default NULL,
  `Validated` datetime default NULL,
  `IsValid` enum('false','true') default 'false',
  `AVSCode` varchar(4) default NULL,
  `Email` varchar(64) NOT NULL default '',
  `Declined` datetime default NULL,
  `LegacyID` integer default NULL,
  `ObjectOwner` integer unsigned default NULL,
  `Account` integer unsigned default NULL,
  `DeclineNotified` datetime default NULL,
  `DeclineNotifyCount` tinyint(4),
  PRIMARY KEY  (`ID`)
) ENGINE=InnoDB
;
CREATE TABLE `TPaymentType` (
  `ID` integer unsigned NOT NULL auto_increment,
  `CodeName` varchar(32) NOT NULL default '',
  `Name` varchar(64) default NULL,
  `Description` text,
  `IsCC` enum('false','true') default 'false',
  `AccountPattern` varchar(64) default NULL,
  `AccountPatternError` text,
  `FormatPattern` varchar(64) default NULL,
  `SecurePattern` varchar(64) default NULL,
  `SecurityCodePattern` varchar(64) default NULL,
  `SecurityCodePatternError` text,
  `RequireAccount` enum('false','true') default 'true',
  `RequireRouting` enum('false','true') default 'false',
  `RequireOwnerName` enum('false','true') default 'false',
  `RequireAddress` enum('false','true') default 'false',
  `RequireExpiration` enum('false','true') default 'false',
  `RequireSecurityCode` enum('false','true') default 'false',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `CodeName` (`CodeName`)
) ENGINE=InnoDB
;
CREATE TABLE `TPaymentTransaction` (
  `ID` integer unsigned NOT NULL auto_increment,
  `Payment` integer unsigned NOT NULL,
  `Batch` integer unsigned default NULL,
  `Description` text,
  `Price` integer unsigned NOT NULL,
  `ShipAddress` integer unsigned default NULL,
  `SubmitCode` varchar(64) default NULL,
  `SubmitDate` datetime default NULL,
  `Authorized` enum('false','true') default 'false',
  `ConfirmCode` varchar(64) default NULL,
  `Cleared` datetime default NULL,
  `Account` integer unsigned NOT NULL,
  `Invoice` integer unsigned default NULL,
  `ProcessDate` datetime default NULL,
  `TransactionCode` varchar(64) default NULL,
  `AVSCode` varchar(4) default NULL,
  `Parent` integer unsigned default NULL,
  `Gate` integer unsigned default NULL,
  `ProcessReason` text,
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `xaction` (`Parent`,`Payment`,`Batch`,`Account`,`Invoice`,`Price`,`SubmitDate`)
) ENGINE=InnoDB
;
CREATE TABLE `TPaymentGate` (
  `ID` integer unsigned NOT NULL auto_increment,
  `CodeName` varchar(32) NOT NULL default '',
  `Name` varchar(64) default NULL,
  `Description` text,
  PRIMARY KEY  (`ID`)
)
;
CREATE TABLE `TAccountTransactionType` (
  `ID` integer unsigned NOT NULL auto_increment,
  `CodeName` varchar(32) NOT NULL default '',
  `Name` varchar(64) default NULL,
  `Description` text,
  `GroupObjectClass` varchar(128) NOT NULL default '',
  `InvoiceShow` enum('false','true') default 'false',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `CodeName` (`CodeName`)
) ENGINE=InnoDB
;
CREATE TABLE `TAccountReferrer` (
  `ID` integer unsigned NOT NULL auto_increment,
  `Account` integer unsigned default NULL,
  `CodeName` varchar(32) default NULL,
  `Name` varchar(64) default NULL,
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `CodeName` (`CodeName`)
) ENGINE=InnoDB
;

CREATE TABLE `TInvoicePayment` (
  `ID` integer unsigned NOT NULL auto_increment,
  `Created` datetime default NULL,
  `Invoice` integer unsigned default NULL,
  `PaymentTransaction` integer unsigned default NULL,
  `Amount` decimal(12,2) NOT NULL default '0.00',
  PRIMARY KEY  (`ID`),
  KEY `invoice` (`Invoice`),
  KEY `PT` (`PaymentTransaction`)
) ENGINE=InnoDB
;
