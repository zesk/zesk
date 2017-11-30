<?php
/**
 * @version $URL$
 * @package zesk
 * @subpackage commerce
 * @author $Author: kent $
 * @copyright Copyright &copy; 2011, Market Acumen, Inc.
 * Created on Mon,Aug 1, 11 at 5:10 PM
 */
?>
DROP TABLE IF EXISTS tempAccountBalanceUpdate;
CREATE TABLE tempAccountBalanceUpdate
SELECT DISTINCT
	A.Account AS ID,
	SUM(A.Amount) as NewBalance,
	SUM(B.Amount) as Balance
FROM <?php echo $this->TablePrefix ?>AccountTransaction A
LEFT OUTER JOIN
	<?php echo $this->TablePrefix ?>AccountTransaction B
ON
	B.ID=A.ID AND B.Reconciled='true'
GROUP BY A.Account
;
ALTER TABLE tempAccountBalanceUpdate ADD PRIMARY KEY id (`ID`);

UPDATE <?php echo $this->TablePrefix ?>Account A,tempAccountBalanceUpdate B
SET A.Balance=B.Balance,A.NewBalance=B.NewBalance
WHERE A.ID=B.ID
;

DROP TABLE tempAccountBalanceUpdate
;

