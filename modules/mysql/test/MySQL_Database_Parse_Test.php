<?php
namespace zesk;

class MySQL_Database_Parse_Test extends Test_Unit {
	protected $load_modules = "mysql";

	public static $schema = <<<EOF
CREATE TABLE `TestTable` (
  `report` int(10) unsigned NOT NULL,
  `campaign` int(10) unsigned NOT NULL,
  `location` int(10) unsigned NOT NULL,
  `AccountCurrencyCode` varchar(64) CHARACTER SET latin1 DEFAULT NULL,
  `AccountDescriptiveName` varchar(64) CHARACTER SET latin1 DEFAULT NULL,
  `AccountTimeZone` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `AllConversionRate` double DEFAULT NULL,
  `AllConversions` double DEFAULT NULL,
  `AllConversionValue` double DEFAULT NULL,
  `AverageCost` double DEFAULT NULL,
  `AverageCpc` double DEFAULT NULL,
  `AverageCpe` double DEFAULT NULL,
  `AverageCpm` double DEFAULT NULL,
  `AverageCpv` double DEFAULT NULL,
  `average_position` double DEFAULT NULL,
  `bid_adjustment` double DEFAULT NULL,
  `CampaignId` int(11) DEFAULT NULL,
  `CampaignName` varchar(64) CHARACTER SET latin1 DEFAULT NULL,
  `CampaignStatus` varchar(7) CHARACTER SET latin1 DEFAULT NULL,
  `clicks_count` int(11) DEFAULT NULL,
  `click_conversion_rate` double DEFAULT NULL,
  `Conversions` double DEFAULT NULL,
  `conversion_value_over_cost` double DEFAULT NULL,
  `Cost` double DEFAULT NULL,
  `cost_per_converted_click` double DEFAULT NULL,
  `cost_per_conversion` double DEFAULT NULL,
  `CrossDeviceConversions` double DEFAULT NULL,
  `clickthrough_rate` double DEFAULT NULL,
  `CustomerDescriptiveName` varchar(64) CHARACTER SET latin1 DEFAULT NULL,
  `Date` timestamp NULL DEFAULT NULL,
  `EngagementRate` double DEFAULT NULL,
  `Engagements` int(11) DEFAULT NULL,
  `ExternalConversionSource` varchar(27) CHARACTER SET latin1 DEFAULT NULL,
  `ExternalCustomerId` int(11) DEFAULT NULL,
  `Id` int(11) DEFAULT NULL,
  `Impressions` int(11) DEFAULT NULL,
  `InteractionRate` double DEFAULT NULL,
  `Interactions` int(11) DEFAULT NULL,
  `InteractionTypes` varchar(64) CHARACTER SET latin1 DEFAULT NULL,
  `IsNegative` varchar(5) CHARACTER SET latin1 DEFAULT NULL,
  `Month` varchar(64) CHARACTER SET latin1 DEFAULT NULL,
  `MonthOfYear` varchar(9) CHARACTER SET latin1 DEFAULT NULL,
  `PrimaryCompanyName` varchar(64) CHARACTER SET latin1 DEFAULT NULL,
  `Quarter` varchar(64) CHARACTER SET latin1 DEFAULT NULL,
  `ValuePerAllConversion` double DEFAULT NULL,
  `ValuePerConversion` double DEFAULT NULL,
  `VideoViewRate` double DEFAULT NULL,
  `VideoViews` int(11) DEFAULT NULL,
  `ViewThroughConversions` int(11) DEFAULT NULL,
  `Week` varchar(64) CHARACTER SET latin1 DEFAULT NULL,
  `Year` int(11) DEFAULT NULL,
  KEY `Ads_Report` (`report`),
  KEY `GoogleAds_Campaign` (`campaign`),
  KEY `Ads_Criterion_Location` (`location`),
  KEY `by_query` (`report`,`location`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
EOF;

	public function test_schema() {
		$db = $this->application->database_registry();
		$table = $db->parse_create_table(self::$schema, __METHOD__);
		$this->assert_instanceof($table, 'zesk\\Database_Table');
		$week = $table->column("Week");
		$this->assert_instanceof($week, 'zesk\\Database_Column');
		$this->assert_false($week->is_increment());
		$this->assert_false($week->is_index());
		$this->assert_true($week->is_text());
		$this->assert_equal($week->size(), 64);
	}

	public function test_parse_pattern() {
		class_exists("\mysql\Database_Parser");
		foreach (array(
			"CREATE TABLE `TestTable` (
				`report` int(10) unsigned NOT NULL,
				`campaign` int(10) unsigned NOT NULL,
				`adgroup` int(10) unsigned NOT NULL,
				`AccountCurrencyCode` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
				`AccountDescriptiveName` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
				`AccountTimeZone` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
				`ActiveViewCpm` double DEFAULT NULL,
				`ActiveViewCtr` double DEFAULT NULL,
				`ActiveViewImpressions` int(11) DEFAULT NULL,
				`ActiveViewMeasurability` double DEFAULT NULL,
				`ActiveViewMeasurableCost` double DEFAULT NULL,
				`ActiveViewMeasurableImpressions` int(11) DEFAULT NULL,
				`ActiveViewViewability` double DEFAULT NULL,
				`AdGroupId` int(11) DEFAULT NULL,
				`AdGroupName` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
				`AdGroupStatus` varchar(7) COLLATE utf8_unicode_ci DEFAULT NULL,
				`AdNetworkType1` varchar(14) COLLATE utf8_unicode_ci DEFAULT NULL,
				`AdNetworkType2` varchar(15) COLLATE utf8_unicode_ci DEFAULT NULL,
				`AllConversionRate` double DEFAULT NULL,
				`AllConversions` double DEFAULT NULL,
				`AllConversionValue` double DEFAULT NULL,
				`AverageCost` double DEFAULT NULL,
				`AverageCpc` double DEFAULT NULL,
				`AverageCpm` double DEFAULT NULL,
				`AverageCpv` double DEFAULT NULL,
				`average_position` double DEFAULT NULL,
				`CampaignId` int(11) DEFAULT NULL,
				`CampaignName` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
				`CampaignStatus` varchar(7) COLLATE utf8_unicode_ci DEFAULT NULL,
				`clicks_count` int(11) DEFAULT NULL,
				`ClickType` varchar(33) COLLATE utf8_unicode_ci DEFAULT NULL,
				`ConversionCategoryName` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
				`click_conversion_rate` double DEFAULT NULL,
				`Conversions` double DEFAULT NULL,
				`ConversionTrackerId` int(11) DEFAULT NULL,
				`ConversionTypeName` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
				`conversion_value_over_cost` double DEFAULT NULL,
				`Cost` double DEFAULT NULL,
				`cost_per_converted_click` double DEFAULT NULL,
				`cost_per_conversion` double DEFAULT NULL,
				`CostPerCurrentModelAttributedConversion` double DEFAULT NULL,
				`CriteriaDestinationUrl` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
				`CriteriaParameters` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
				`CriteriaStatus` varchar(7) COLLATE utf8_unicode_ci DEFAULT NULL,
				`CriteriaTypeName` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
				`CrossDeviceConversions` double DEFAULT NULL,
				`clickthrough_rate` double DEFAULT NULL,
				`CurrentModelAttributedConversions` double DEFAULT NULL,
				`CurrentModelAttributedConversionValue` double DEFAULT NULL,
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci",
			"CREATE TABLE `TestTable` (
				`report` int(10) unsigned NOT NULL,
				`campaign` int(10) unsigned NOT NULL,
				`adgroup` int(10) unsigned NOT NULL,
				`AccountCurrencyCode` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
				`AccountDescriptiveName` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
				`AccountTimeZone` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
				`ActiveViewCpm` double DEFAULT NULL,
				`ActiveViewCtr` double DEFAULT NULL,
				`ActiveViewImpressions` int(11) DEFAULT NULL,
				`ActiveViewMeasurability` double DEFAULT NULL,
				`ActiveViewMeasurableCost` double DEFAULT NULL,
				`ActiveViewMeasurableImpressions` int(11) DEFAULT NULL,
				`ActiveViewViewability` double DEFAULT NULL,
				`AdGroupId` int(11) DEFAULT NULL,
				`AdGroupName` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
				`AdGroupStatus` varchar(7) COLLATE utf8_unicode_ci DEFAULT NULL,
				`AdNetworkType1` varchar(14) COLLATE utf8_unicode_ci DEFAULT NULL,
				`AdNetworkType2` varchar(15) COLLATE utf8_unicode_ci DEFAULT NULL,
				`AllConversionRate` double DEFAULT NULL,
				`AllConversions` double DEFAULT NULL,
				`AllConversionValue` double DEFAULT NULL,
				`AverageCost` double DEFAULT NULL,
				`AverageCpc` double DEFAULT NULL,
				`AverageCpe` double DEFAULT NULL,
				`AverageCpm` double DEFAULT NULL,
				`AverageCpv` double DEFAULT NULL,
				`average_position` double DEFAULT NULL,
				`CampaignId` int(11) DEFAULT NULL,
				`CampaignName` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
				`CampaignStatus` varchar(7) COLLATE utf8_unicode_ci DEFAULT NULL,
				`clicks_count` int(11) DEFAULT NULL,
				`ClickType` varchar(33) COLLATE utf8_unicode_ci DEFAULT NULL,
				`ConversionCategoryName` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
				`click_conversion_rate` double DEFAULT NULL,
				`Conversions` double DEFAULT NULL,
				`ConversionTrackerId` int(11) DEFAULT NULL,
				`ConversionTypeName` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
				`conversion_value_over_cost` double DEFAULT NULL,
				`Cost` double DEFAULT NULL,
				`cost_per_converted_click` double DEFAULT NULL,
				`cost_per_conversion` double DEFAULT NULL,
				`CostPerCurrentModelAttributedConversion` double DEFAULT NULL,
				`CriteriaDestinationUrl` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
				`CriteriaParameters` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
				`CriteriaStatus` varchar(7) COLLATE utf8_unicode_ci DEFAULT NULL,
				`CriteriaTypeName` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
				`CrossDeviceConversions` double DEFAULT NULL,
				`clickthrough_rate` double DEFAULT NULL,
				`CurrentModelAttributedConversions` double DEFAULT NULL,
				`CurrentModelAttributedConversionValue` double DEFAULT NULL,
				`CustomerDescriptiveName` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
				`Date` timestamp NULL DEFAULT NULL,
				`DayOfWeek` varchar(9) COLLATE utf8_unicode_ci DEFAULT NULL,
				`Device` varchar(15) COLLATE utf8_unicode_ci DEFAULT NULL,
				`EffectiveDestinationUrl` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
				`EngagementRate` double DEFAULT NULL,
				`Engagements` int(11) DEFAULT NULL,
				`ExternalConversionSource` varchar(27) COLLATE utf8_unicode_ci DEFAULT NULL,
				`ExternalCustomerId` int(11) DEFAULT NULL,
				`GmailForwards` int(11) DEFAULT NULL,
				`GmailSaves` int(11) DEFAULT NULL,
				`GmailSecondaryClicks` int(11) DEFAULT NULL,
				`Impressions` int(11) DEFAULT NULL,
				`InteractionRate` double DEFAULT NULL,
				`Interactions` int(11) DEFAULT NULL,
				`InteractionTypes` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
				`IsNegative` varchar(5) COLLATE utf8_unicode_ci DEFAULT NULL,
				`Month` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
				`MonthOfYear` varchar(9) COLLATE utf8_unicode_ci DEFAULT NULL,
				`PrimaryCompanyName` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
				`Quarter` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
				`Slot` varchar(11) COLLATE utf8_unicode_ci DEFAULT NULL,
				`ValuePerAllConversion` double DEFAULT NULL,
				`ValuePerConversion` double DEFAULT NULL,
				`ValuePerCurrentModelAttributedConversion` double DEFAULT NULL,
				`VideoQuartile100Rate` double DEFAULT NULL,
				`VideoQuartile25Rate` double DEFAULT NULL,
				`VideoQuartile50Rate` double DEFAULT NULL,
				`VideoQuartile75Rate` double DEFAULT NULL,
				`VideoViewRate` double DEFAULT NULL,
				`VideoViews` int(11) DEFAULT NULL,
				`ViewThroughConversions` int(11) DEFAULT NULL,
				`Week` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
				`Year` int(11) DEFAULT NULL
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci",
			"CREATE TABLE `TestTable` (\n  `report` int(10) unsigned NOT NULL,\n  `campaign` int(10) unsigned NOT NULL,\n  `adgroup` int(10) unsigned NOT NULL,\n  `AccountCurrencyCode` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,\n  `AccountDescriptiveName` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,\n  `AccountTimeZone` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,\n  `ActiveViewCpm` double DEFAULT NULL,\n  `ActiveViewCtr` double DEFAULT NULL,\n  `ActiveViewImpressions` int(11) DEFAULT NULL,\n  `ActiveViewMeasurability` double DEFAULT NULL,\n  `ActiveViewMeasurableCost` double DEFAULT NULL,\n  `ActiveViewMeasurableImpressions` int(11) DEFAULT NULL,\n  `ActiveViewViewability` double DEFAULT NULL,\n  `AdGroupId` int(11) DEFAULT NULL,\n  `AdGroupName` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,\n  `AdGroupStatus` varchar(7) COLLATE utf8_unicode_ci DEFAULT NULL,\n  `AdNetworkType1` varchar(14) COLLATE utf8_unicode_ci DEFAULT NULL,\n  `AdNetworkType2` varchar(15) COLLATE utf8_unicode_ci DEFAULT NULL,\n  `AllConversionRate` double DEFAULT NULL,\n  `AllConversions` double DEFAULT NULL,\n  `AllConversionValue` double DEFAULT NULL,\n  `AverageCost` double DEFAULT NULL,\n  `AverageCpc` double DEFAULT NULL,\n  `AverageCpe` double DEFAULT NULL,\n  `AverageCpm` double DEFAULT NULL,\n  `AverageCpv` double DEFAULT NULL,\n  `average_position` double DEFAULT NULL,\n  `CampaignId` int(11) DEFAULT NULL,\n  `CampaignName` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,\n  `CampaignStatus` varchar(7) COLLATE utf8_unicode_ci DEFAULT NULL,\n  `clicks_count` int(11) DEFAULT NULL,\n  `ClickType` varchar(33) COLLATE utf8_unicode_ci DEFAULT NULL,\n  `ConversionCategoryName` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,\n  `click_conversion_rate` double DEFAULT NULL,\n  `Conversions` double DEFAULT NULL,\n  `ConversionTrackerId` int(11) DEFAULT NULL,\n  `ConversionTypeName` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,\n  `conversion_value_over_cost` double DEFAULT NULL,\n  `Cost` double DEFAULT NULL,\n  `cost_per_converted_click` double DEFAULT NULL,\n  `cost_per_conversion` double DEFAULT NULL,\n  `CostPerCurrentModelAttributedConversion` double DEFAULT NULL,\n  `CriteriaDestinationUrl` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,\n  `CriteriaParameters` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,\n  `CriteriaStatus` varchar(7) COLLATE utf8_unicode_ci DEFAULT NULL,\n  `CriteriaTypeName` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,\n  `CrossDeviceConversions` double DEFAULT NULL,\n  `clickthrough_rate` double DEFAULT NULL,\n  `CurrentModelAttributedConversions` double DEFAULT NULL,\n  `CurrentModelAttributedConversionValue` double DEFAULT NULL,\n  `CustomerDescriptiveName` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,\n  `Date` timestamp NULL DEFAULT NULL,\n  `DayOfWeek` varchar(9) COLLATE utf8_unicode_ci DEFAULT NULL,\n  `Device` varchar(15) COLLATE utf8_unicode_ci DEFAULT NULL,\n  `EffectiveDestinationUrl` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,\n  `EngagementRate` double DEFAULT NULL,\n  `Engagements` int(11) DEFAULT NULL,\n  `ExternalConversionSource` varchar(27) COLLATE utf8_unicode_ci DEFAULT NULL,\n  `ExternalCustomerId` int(11) DEFAULT NULL,\n  `GmailForwards` int(11) DEFAULT NULL,\n  `GmailSaves` int(11) DEFAULT NULL,\n  `GmailSecondaryClicks` int(11) DEFAULT NULL,\n  `Impressions` int(11) DEFAULT NULL,\n  `InteractionRate` double DEFAULT NULL,\n  `Interactions` int(11) DEFAULT NULL,\n  `InteractionTypes` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,\n  `IsNegative` varchar(5) COLLATE utf8_unicode_ci DEFAULT NULL,\n  `Month` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,\n  `MonthOfYear` varchar(9) COLLATE utf8_unicode_ci DEFAULT NULL,\n  `PrimaryCompanyName` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,\n  `Quarter` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,\n  `Slot` varchar(11) COLLATE utf8_unicode_ci DEFAULT NULL,\n  `ValuePerAllConversion` double DEFAULT NULL,\n  `ValuePerConversion` double DEFAULT NULL,\n  `ValuePerCurrentModelAttributedConversion` double DEFAULT NULL,\n  `VideoQuartile100Rate` double DEFAULT NULL,\n  `VideoQuartile25Rate` double DEFAULT NULL,\n  `VideoQuartile50Rate` double DEFAULT NULL,\n  `VideoQuartile75Rate` double DEFAULT NULL,\n  `VideoViewRate` double DEFAULT NULL,\n  `VideoViews` int(11) DEFAULT NULL,\n  `ViewThroughConversions` int(11) DEFAULT NULL,\n  `Week` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,\n  `Year` int(11) DEFAULT NULL,\n  KEY `by_query` (`report`,`campaign`,`adgroup`),\n  KEY `Ads_Report` (`report`),\n  KEY `GoogleAds_Campaign` (`campaign`),\n  KEY `GoogleAds_AdGroup` (`adgroup`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci",
		) as $index => $code) {
			$length = strlen($code);
			$this->assert_equal(preg_match(MYSQL_PATTERN_CREATE_TABLE, strtr($code, "\n", " "), $matches), 1, "Code # $index ($length)");
		}
	}
}
