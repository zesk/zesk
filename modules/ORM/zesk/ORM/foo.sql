SELECT `X`.*
FROM `Test_SiteMonitor` AS `X`
INNER JOIN `Test_Site` AS `S`
ON `S`.`ID`=`X`.`Site`
INNER JOIN `Test_Account` AS `A` ON `A`.`ID`=`S`.`Account`
WHERE `A`.`Cancelled` IS NULL;

SELECT `X`.*
FROM `Test_SiteMonitor` AS `X`
INNER JOIN `Test_Site` AS `S` ON `S`.`ID`=`X`.`Site`
INNER JOIN `Test_Site` AS `Site` ON `Site`.`ID`=`X`.`Site`
INNER JOIN `Test_Account` AS `A` ON `A`.`ID`=`Site`.`Account`
WHERE `A`.`Cancelled` IS NULL;
