<?php

/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/commerce/classes/account.inc $
 * @package zesk
 * @subpackage objects
 */
namespace zesk;

/**
 * Account
 *
 * @author kent
 */
class Account extends Object {
	public function compute_balance() {
		$query = $this->application->class_query("Account_Transaction", "X")->what("*Balance", "SUM(X.Amount)")->where("Account", $this);
		
		$this->Balance = doubleval($query->where("Reconciled", true)->one("Balance", 0));
		$this->NewBalance = doubleval($query->where("Reconciled", false)->one("Balance", 0));
		if ($this->changed()) {
			$this->store();
		}
	}
	function balance_as_of(Timestamp $dt, $includingDate = true) {
		$balance = $this->member("CurrentBalance");
		$balance = $balance->copyObject($this);
		$where = $balance->option_array("Where");
		$cmp = $includingDate ? "le" : "lt";
		$where["EffectiveDate:$cmp"] = $dt->__toString();
		$balance->set_option("Where", $where);
		return $balance->get();
	}
	function payment() {
		return $this->Payment;
	}
	function payment_account() {
		if ($this->member_is_empty("PaymentAccount")) {
			$this->PaymentAccount = $this;
			$this->store();
		}
		if ($this->is_self_paid()) {
			return $this;
		}
		return $this->PaymentAccount;
	}
	function is_self_paid() {
		return $this->payment_account()->id() === $this->id();
	}
	function payment_is_valid() {
		if ($this->is_self_paid()) {
			$payment = $this->find_valid_payment();
			return $payment instanceof Payment;
		}
		return $this->payment_account()->payment_is_valid();
	}
	function find_valid_payment() {
		if ($this->payment_is_valid()) {
			return $this->payment();
		}
		$payments = $this->application->query_select("Payment")
			->where("IsValid", true)
			->where("Account", $this)
			->order_by("-Created")
			->object_iterator();
		foreach ($payments as $payment) {
			/* @var $payment Payment */
			$result = $payment->checkValid();
			if ($result === true) {
				$this->set_member("Payment", $payment);
				$this->store();
				return $payment;
			}
		}
		return false;
	}
	function has_valid_payment() {
		$paymentAccount = $this->payment_account();
		if ($paymentAccount->member_is_empty("Payment")) {
			return false;
		}
		$payment = $paymentAccount->payment();
		if (!$payment instanceof Payment) {
			return false;
		}
		if ($payment->is_valid()) {
			return true;
		}
		return false;
	}
	function referrerAccount() {
		$ref = $this->memberGet("Referrer");
		if ($ref) {
			$account = $ref->account();
			if ($account) {
				return $account;
			}
		}
		return false;
	}
	function manager_account() {
		return $this->memberGet("ManagerAccount");
	}
	function update_next_bill_date() {
		$lastBillDate = $this->LastBillDate;
		if (!$lastBillDate) {
			return false;
		}
		$this->NextBillDate = $this->next_bill_date_from($lastBillDate);
		return $this->store();
	}
	function bill_time_untis() {
		$units = $this->BillTimeUnit;
		if (empty($units)) {
			$units = $this->option("bill_time_unit", "month");
			$this->BillTimeUnit = $units;
			$unitCount = $this->option_integer("bill_time_unit_count", 1);
			$this->BillTimeUnitCount = $unitCount;
			$this->store();
		} else {
			$unitCount = $this->BillTimeUnitCount;
		}
		return array(
			$units,
			$unitCount
		);
	}
	private static function next_bill_date_from(Timestamp $here) {
		$here = clone $here;
		list($units, $unitCount) = $this->billTimeUnits();
		$here->addUnit($units, $unitCount);
		return $here;
	}
	function billedRecently(Timestamp $afterDate) {
		$query = $this->application->query_select("Payment_Transaction")->what("*LastDate", "MAX(X.ProcessDate)")->where("Account", $this);
		$query->where("Authorized", true)->where("ProcessDate|>=", $afterDate);
		$result = $query->one("LastDate");
		if ($result) {
			return Timestamp::factory($result);
		}
		return null;
	}
	
	//	// TODO: Prior to billing, update IsBillable in Account
	//	function referred_total_accounts($asOfDate=false)
	//	{
	//		if (!$asOfDate instanceof Timestamp) {
	//			$asOfDate = Timestamp::instance();
	//		}
	//		/*
	//		 * Count Accounts referred
	//		 * Added "Invoice" which means an account is not "counted" unless it's invoiced.
	//		 */
	//		$q = $this->query_select("X");
	//		$q->what("*Total", "COUNT(DISTINCT Account.ID)");
	//
	//		//		$q->addLink("StatsSite");
	//		$q->addLink("AccountReferrer", "Account.Referrer");
	//		$q->addJoin("Invoice", "inner", array("Account.ID:" => array("Invoice.Account", "Invoice.PaymentAccount")));
	//		$q->setWhere(
	//		array(
	//		"AccountReferrer.Account" => $this,
	//		"Account.ActivateDateTime:le" => array(null,$asOfDate),
	//		"Account.Cancelled:ge" => array(null, $asOfDate),
	//		)
	//		);
	//
	///*		$sql =
	//		"SELECT COUNT(DISTINCT A.ID) as Total
	//			FROM zeskAccount A
	//			INNER JOIN zeskStatsSite S ON S.Account=A.ID
	//			INNER JOIN zeskAccountReferrer R ON R.ID=A.Referrer
	//			INNER JOIN zeskInvoice I ON (I.Account=A.ID OR I.PaymentAccount=A.ID)
	//			WHERE
	//				R.Account=$this->ID
	//				AND S.ActivateDateTime <= '".$asOfDate->__toString()."'
	//				AND (S.Deactivated IS null OR S.Deactivated >= '".$asOfDate->__toString()."')
	//			";
	//*/
	//		$result = $q->oneQuery("Total", 0);
	//
	////		echo "\n\n$result = \n\t";
	////		echo $q->toSQL();
	////		echo "\n\n";
	//
	//		return $result;
	//	}
	function managed_total_accounts($asOfDate = false) {
		if (!$asOfDate instanceof Timestamp) {
			$asOfDate = Timestamp::now();
		}
		$q = $this->query_select()->where("ManagerAccount", $this)->where("Cancelled|<=", array(
			$asOfDate,
			null
		));
		$q->what("*Total", "COUNT(ID)");
		return $q->one_integer("Total");
	}
	private function determine_commission() {
		$commission = $this->Commission;
		if ($commission) {
			return $commission;
		}
		$accountType = $this->AccountType;
		if (!$accountType) {
			return null;
		}
		return $accountType->DefaultCommission;
	}
	function commission() {
		$commission = $this->_commission();
		if (!$commission) {
			return null;
		}
		if (!$commission->isDiscount()) {
			return $commission;
		}
		return null;
	}
	function discount() {
		$commission = $this->_commission();
		if (!$commission) {
			return null;
		}
		if ($commission->isDiscount()) {
			return $commission;
		}
		return null;
	}
	function referrerCommission() {
		$referrer = $this->referrerAccount();
		if (!$referrer) {
			return false;
		}
		return $referrer->commission();
	}
	
	//	private function _lastInvoiceQuery(Timestamp $fromDate=null)
	//	{
	//		$q = CModelClass::queryFromType("Invoice");
	//		$where["Account"] = $this;
	//		if ($fromDate instanceof Timestamp && !$fromDate->isEmpty()) {
	//			$where["BillDate:lt"] = $fromDate;
	//		}
	//		$q->setWhere($where);
	//		$q->setOrderBy("-BillDate");
	//		$q->setLimit(1);
	//		return $q;
	//	}
	//
	//	function lastInvoice(Timestamp $fromDate)
	//	{
	//		$q = $this->_lastInvoiceQuery($fromDate);
	//		return $q->classQueryOne();
	//	}
	//
	//	function lastInvoiceDate(/*Timestamp */$fromDate=null, $create=false)
	//	{
	//		$q = $this->_lastInvoiceQuery($fromDate);
	//		$q->setWhat("BillDate");
	//		$result = $q->oneQueryDateTime("BillDate");
	//		if ($result instanceof Timestamp) {
	//			return $result;
	//		}
	//		if (!$create) {
	//			return $result;
	//		}
	//		return new Timestamp();
	//	}
	function killAccount() {
		$this->application->query_update(__CLASS__)->values(array(
			"Cancelled" => "now",
			"IsBillable" => false,
			"NextBillDate" => null,
			"PaymentAccount" => null,
			"ManagerAccount" => null,
			"Payment" => null
		))->where(array(
			"id" => $this->id()
		));
	}
	
	/**
	 *
	 * @param
	 *        	sdate is the start date of this invoice
	 * @param
	 *        	edate returns the resulting end date of the clickthroughs
	 * @return array of CInvoiceParameter
	 */
	function generateInvoiceParameters(Timestamp $billDate, $forceBillUnit = false) {
		$p = new Invoice_Parameters($this, $billDate, $forceBillUnit);
		
		$now = Timestamp::now();
		if ($billDate->difference($now, kTimeUnit_Day) >= 21) {
			$p->messageAdd(__("Your account is behind in our billing, please note the effective date of this invoice. We appreciate your understanding!"));
			$p->messageAdd(__("Your account will be billed weekly until it is up to date, at which point it will be billed monthly."));
		}
		$links = $this->find_billable_items();
		if (!$links) {
			return false;
		}
		foreach ($links as $name) {
			$member = $this->member($name);
			$object = $member->linkedInstance(ID_NOOP);
			if (!$object) {
				continue;
			}
			$object->invoiceParameters($p);
		}
		return $p;
	}
	function generate_discounts(Invoice_Parameters $p) {
		$result = array();
		$links = $this->find_billable_items();
		if (!$links) {
			return $result;
		}
		foreach ($links as $name) {
			$member = $this->member($name);
			$object = $member->linkedInstance(ID_NOOP);
			$object->invoiceDiscount($p);
		}
		return true;
	}
	private function find_billable_items() {
		$c = $this->modelClass();
		return $c->linkFindByOption("Billable");
	}
	
	/*
	 * Commissions and Invoices
	 * @todo Fix this, rewrite
	 */
	private function _transactionQuery($invoiceID, $endDate) {
		$accountID = $this->id();
		
		$q = Object::class_query("Account_Transaction");
		$q->link("Account_Transaction_Type");
		$q->link("Invoice");
		$q->link("Invoice_Item");
		$q->where(array(
			"Account" => $this,
			"EffectiveDate|<=" => $endDate,
			//				"InvoiceItem.Invoice" => array($invoiceID, null), /* OR */
			array(
						/* Things already linked to this invoice */
						array(
					"Invoice.PaymentInvoice" => $invoiceID
				),	 /* OR */
						array(
					"Invoice.PaymentInvoice" => null,
					"*Invoice.PaymentAccount" => "AccountTransaction.Account",
					"*Invoice.Account|!=" => "AccountTransaction.Account"
				),	 /* OR */
						array(
					"InvoiceItem.Invoice" => $invoiceID
				),	 /* OR */
						/* Unreconciled transactions paid by us */
						array(
					"Reconciled" => false,
					"Invoice.PaymentAccount" => $accountID,
					"AccountTransactionType.InvoiceShow" => true,
					"InvoiceItem.ID" => null
				),	 /* OR */
						/* Reconciled transactions paid to us, not appearing on other invoices */
						array(
					"Reconciled" => true,
					"Invoice.PaymentAccount:ne" => $accountID,
					"AccountTransactionType.InvoiceShow" => true,
					"InvoiceItem.ID" => null
				),	 /* OR */
						/* Reconciled transactions paid to us, not appearing on other invoices */
						array(
					"Reconciled" => false,
					"Invoice.ID:ne" => $invoiceID,
					"Invoice.PaymentAccount" => $accountID
				),	 /* OR */
						array(
					"Reconciled" => false,
					"AccountTransaction.Invoice" => NULL,
					"AccountTransaction.InvoiceItem" => NULL,
					"AccountTransaction.PaymentTransaction" => NULL,
					"AccountTransactionType.InvoiceShow" => true
				) /* OR */
				//TODO: When PaymentAccount=CommissionAccount, copy the commissions into this invoice
			)
		));
		$q->order_by(array(
			"EffectiveDate",
			"OrderIndex"
		));
		//		CDebug::dump($q->toSQL()); exit();
		return $q;
	}
	function update_balance_all() {
		$sql = explode(";", Template::instance(ZESK_COMMERCE_ROOT . "theme/account/update-balances.sql.tpl"));
		return $this->database()->query($sql) ? true : false;
	}
	function issue_check($checkNumber, Timestamp $when, $amount, $description = "") {
		$fields = array(
			"TransactionGroup" => $checkNumber,
			"Account" => $this,
			"TransactionType" => "check",
			"EffectiveDate" => $when,
			"Description" => $description,
			"Amount" => -abs($amount),
			"Reconciled" => true
		);
		return Account_Transaction::register_transaction($fields);
	}
	function is_cancelled() {
		return !$this->member_is_empty("Cancelled");
	}
	protected function hook_permission(User $user, $action) {
		if ($user->member_integer("Account") === $this->id()) {
			return true;
		}
		return false;
	}
}


//
//	function widgetMostRecentInvoice(/* CContext $context */)
//	{
//		$t = $context->pushTemplate("AccountMostRecentInvoice");
//		$invoice = $this->lastInvoice(Timestamp::now());
//		$t->set("Account", $this);
//		$t->set("Invoice", $invoice);
//		return $context->popTemplate();
//	}
//
//	function widgetInvoice(/* CContext $context */)
//	{
//		$context->parameterSet("Account", $this->id());
//		return CInvoice::widgetGenerate($context);
//	}

//	function widgetTransactionHistory(/* CContext $context */)
//	{
//		$id = $this->id();
//
//		$sql = "
//			SELECT
//				SUM(A.Amount) as Total,
//				SUM(B.Amount) as Uncollected,
//				SUM(C.Amount) as Collected,
//				SUM(D.Amount) as Uncollected_Commission,
//				SUM(E.Amount) as Collected_Commission,
//				MONTH(A.EffectiveDate) as Month,
//				YEAR(A.EffectiveDate) as Year
//			FROM zeskAccountTransaction A
//			LEFT OUTER JOIN zeskAccountTransaction B ON B.ID=A.ID AND B.Amount > 0 AND B.Reconciled='false'
//			LEFT OUTER JOIN zeskAccountTransaction C ON C.ID=A.ID AND C.Amount > 0 AND C.Reconciled='true'
//			LEFT OUTER JOIN zeskAccountTransaction D ON D.ID=A.ID AND D.Amount < 0 AND D.Reconciled='false'
//			LEFT OUTER JOIN zeskAccountTransaction E ON E.ID=A.ID AND E.Amount < 0 AND E.Reconciled='true'
//			WHERE
//			A.Account=$id
//			AND A.Amount IS NOT NULL
//			AND A.EffectiveDate > '2004-01-01 00:00:00'
//			GROUP BY YEAR(A.EffectiveDate),MONTH(A.EffectiveDate)
//			ORDER BY A.EffectiveDate
//		";
//
//		$w =
//		new CControlObjectList(
//		array(
//		"ModelType" => "AccountTransaction",
//		"ShowPager" => true,
//		)
//		);
//		$q = new CModelQueryDatabase($this->modelClass(), $sql);
//		$cid = $this->currencyID();
//		$w->setIterator($q);
//		$w->setWidgets(
//		array(
//		"Year" 						=> "view:integer(HeaderText='[[Year]]',Align=center)",
//		"Month" 					=> "view:integer(HeaderText='[[Month]]',Align=left)",
//		"Uncollected" 				=> "view:price(Currency=$cid,Align=right,HeaderText='[[Account:Uncollected]]')",
//		"Uncollected_Commission"	=> "view:price(Currency=$cid,Align=right,HeaderText='[[Account:Uncollected<br />Commissions]]')",
//		"Collected" 				=> "view:price(Currency=$cid,Align=right,HeaderText='[[Account:Collected]]')",
//		"Collected_Commission"		=> "view:price(Currency=$cid,Align=right,HeaderText='[[Account:Collected<br />Commissions]]')",
//		"Total"						=> "view:price(Currency=$cid,Align=right,HeaderText='[[Account:Total]]')",
//		)
//		);
//		return $w->toResponse($context, "AccountHistory", $q);
//	}
//
//
//	function widgetStatus(/* CContext $context */)
//	{
//		$action = $context->parameterGet("Action");
//		switch ($action) {
//			case "payment":
//			{
//				$account = $context->parameterGetObjectRedirect("Account");
//				$payment = new CPayment(array("Account" => $account));
//				$result = $payment->executeAction(kAction_New, $context, "", $payment);
//				$account->find_valid_payment();
//				return $result;
//			}
//			case "invoice":
//			{
//				$invoice = $context->parameterGetObjectRedirect("Invoice");
//				return $payment->executeAction(kAction_Pay, $invoice, "", $invoice);
//			}
//		}
//		$t = $context->pushTemplate("AccountLoginStatus");
//		// Invalid invoices
//		$q = CModelClass::queryFromType("Invoice");
//		$q->inheritOptions(
//		array(
//		"Where" =>
//		array(
//		"BillDate:le" => "now",
//		"AmountDueNew:gt" => 0,
//		)
//		)
//		);
//		$q->setLimit(5);
//		if (!$q->addPermissions(kAction_List)) {
//			$t->noblock("Invoice");
//		} else {
//			$t->blockMixed("Invoice", $q);
//		}
//		// Invalid payments
//		$q = CModelClass::queryFromType("Payment");
//		$q->inheritOptions(
//		array(
//		"Links" => "Account.Payment",
//		"Where" =>
//		array(
//		"Account.IsBillable" => true,
//		array(
//		array(
//		"Payment:ne" => null,
//		"Payment.Declined" => null,
//		"Payment.ExpireDate:le" => "now"
//		),
//		array(
//		"Payment" => null,
//		),
//		)
//		)
//		)
//		);
//		$q->setLimit(5);
//		if (!$q->addPermissions()) {
//			$t->noblock("Payment");
//		} else {
//			$t->blockMixed("Payment", $q);
//		}
//		$t->set("debug", "");
//		return $context->popTemplate();
//	}

/*
 function widgetPayReferrer( CContext $context)
 {
 $where = array("TransactionType" => "check");

 $uriPrefix = "/".$context->parameterPrefix();
 $checkTransaction = $context->parameterPeekObject("AccountTransaction");
 if ($checkTransaction instanceof AccountTransaction) {
 $ttype = $checkTransaction->TransactionType->CodeName;
 if (strcasecmp($ttype, "check") == 0) {
 $where["EffectiveDate:lt"] = $checkTransaction->EffectiveDate;
 } else {
 $checkTransaction = null;
 return $context->http::redirect($uriPrefix);
 }
 } else {
 $checkTransaction = null;
 }

 if ($this->member_is_empty("Currency")) {
 $this->Currency = "USD";
 $this->store();
 }

 // Page
 $t = $context->pushForm("AccountPayReferrer");

 // Setup
 $options 	= array("Where" => $where, "Limit" => 1, "OrderBy" => array("-EffectiveDate","-OrderIndex"));
 $lastCheck	= $this->memberGetAs(kType_Object,"Transactions", false, $options);
 if (!$lastCheck) {
 $lastDate = $this->Created;
 } else {
 $lastDate = $lastCheck->EffectiveDate;
 }
 if ($checkTransaction) {
 $thisDate = $checkTransaction->EffectiveDate;
 $options =
 array(
 "Where" => array("TransactionType" => "check", "EffectiveDate:gt" => $thisDate),
 "Limit" => 1,
 "OrderBy" => array("EffectiveDate","OrderIndex")
 );
 $nextCheck	= $this->memberGetAs(kType_Object,"Transactions", false, $options);
 } else {
 $thisDate = $t->widgetModelGet("PayDate");
 $nextCheck	= "";
 }
 $t->set("LastDate", $lastDate);
 $t->set("NextCheck", $nextCheck);

 $t->set("URLPrefix", $uriPrefix);
 $t->set("Account", $this);

 $t->set("IssuedCheck", $checkTransaction);

 $errors = $t->controlErrors("PayDate");
 $t->errorBlock($errors, "ControlError");
 $t->booleanBlock($checkTransaction, false, "NeedWidgets");
 if (count($errors) == 0 && !$thisDate->isEmpty())
 {
 $lastBalance = $this->balanceAsOf($lastDate);

 $t->set("LastCheck", $lastCheck);
 $t->set("LastBalance", $lastBalance);

 $options =
 array(
 "What"		=> array("Total" => "SUM(Amount)", "Type" => "AccountTransactionType.Name"),
 "Links"		=> "AccountTransactionType",
 "Distinct"	=> true,
 "Where" 	=>
 array(
 "TransactionType:ne" => "check",
 "EffectiveDate:gt" => $lastDate,
 "EffectiveDate:le" => $thisDate,
 "Account" => $this,
 "Reconciled" => true
 ),
 "GroupBy"	=> "TransactionType",
 "OrderBy"	=> "AccountTransactionType.Name",
 "ByRow"		=> true,
 );
 $paySummary	= CModelClass::queryFromType("AccountTransaction", $options);

 $t->blockMixed("XSummary", $paySummary);

 $options["What"]  = array("Total" => "SUM(Amount)");
 $options["GroupBy"] = false;

 $payQuery = CModelClass::queryFromType("AccountTransaction", $options);
 $total = $payQuery->oneQuery("Total");
 if ($lastBalance instanceof CModel) {
 $total += $lastBalance->get();
 }
 $t->set("Total", $total);

 $checkNumber = $t->widgetModel("CheckNumber");
 $checkTotal = $t->widgetModel("CheckTotal");

 $errors = $t->controlErrors();

 if (count($errors) == 0
 && !$checkNumber->isEmpty()
 && !$checkTotal->isEmpty()
 && $t->widgetModelGetAs("IssueCheck", kType_Boolean))
 {
 $x = $this->issueCheck($checkNumber, $thisDate, $checkTotal->getAsReal(), "[[Account:comission-check=Commission check]]");
 if ($x instanceof CError) {
 $this->errorBlock($x, "ControlError");
 } else {
 return $context->http::redirect(CDirectory::path($uriPrefix, $x->id()));
 }
 }
 $t->set("TotalCheck", $total);

 $options =
 array(
 "What"		=> array("Total" => "SUM(Amount)", "Account" => "Invoice.Account"),
 "Links"		=> "Invoice",
 "Where" 	=>
 array(
 "TransactionType" => "Commission",
 "EffectiveDate:gt" => $lastDate,
 "EffectiveDate:le" => $thisDate,
 "Account" => $this,
 "Reconciled" => true
 ),
 "GroupBy"	=> "Invoice.Account",
 "OrderBy"	=> "Invoice.Account",
 "ByRow"		=> true,
 );

 $paySummary	= CModelClass::queryFromType("AccountTransaction", $options);

 $t->blockMixed("AccountSummary", $paySummary);
 $t->set("debug", $paySummary->toSQL());
 }

 return $context->popForm();
 }
 */

/*====================================================================================*\
 *	Tasks
 \*------------------------------------------------------------------------------------*/
//	function taskUpdateBalances()
//	{
//		self::updateBalanceAll();
//	}

//	function taskBilling()
//	{
//		require_once(_ZROOT . "code/modules/commerce/AccountBilling.inc");
//		$billing = new AccountBilling();
//		$billing->billing();
//	}
//
//	function taskRepairBillable()
//	{
//		$aa = CModelObject::fetchAll("Account");
//		$n = 0;
//		$x = 0;
//		$now = Timestamp::now();
//		$domainPrefix = CSiteConfig::siteAddress();
//		foreach ($aa as $a) {
//			$n++;
//			$id = $a->id();
//			$name = $a->memberGetName();
//			$actualIsBillable = $a->isBillable();
//			$checkIsBillable = $a->_computeIsBillable();
//			if ($actualIsBillable == $checkIsBillable)
	//			continue;
	//
	//			if ($a->member_is_empty("Created")) {
	//				echo "$id: No creation date ... determining...\n";
	//				if (!$a->legacyDetermineCreationDate()) {
	//					echo "$id: Still no creation date! Make inactive!\n";
	//					$a->set_member("IsBillable", false);
	//					$a->set_member("Cancelled", "2004-01-01 13:13:13");
	//					$a->set_member("BillingNotes", $a->memberGet("BillingNotes") . " Cancelled by self::taskRepairActive, no creation date");
	//					$a->store();
	//					$x++;
	//					continue;
	//				}
	//			}
	//
	//			$created = $a->memberGet("Created");
	//			$t = $a->memberGet("AccountType");
	//			if (!$t) {
	//				echo "$id: No account type! Fix: $domainPrefix/object/account/edit/$id\n";
	//				$x++;
	//			} else if (!$t->isBillable()) {
	//				if ($created->difference($now, kTimeUnit_Month) >= 1) {
	//					$a->set_member("IsBillable", false);
	//					$cancelDate = new Timestamp($created);
	//					$cancelDate->add(0,1);
	//					$a->set_member("Cancelled", $cancelDate);
	//					$a->set_member("BillingNotes", $a->memberGet("BillingNotes") . " self::taskRepairActive cancelled account. Account is beyond trial period.");
	//					echo "$id: Account is beyond trial period. Set cancelled on ". $cancelDate->__toString() . "\n";
	//					$x++;
	//					$a->store();
	//				} else {
	//					echo "$id: Still in trial ". $cancelDate->__toString() . "\n";
	//				}
	//			}
	//			else
		//			{
		//				echo
		//				"$id: $name: Active mismatch: Actual: $actualIsBillable Compute=$checkIsBillable\n".
		//				"Check out account $domainPrefix/object/account/edit/$id\n";
		//			}
		//			$x++;
		//
		//		}
		//		echo "$n Accounts checked, $x problems.\n";
		//	}
		//
		//	function taskCheckBillable()
		//	{
		//		$aa = CModelObject::fetchAll("Account");
		//		$n = 0;
		//		$x = 0;
		//		foreach ($aa as $a) {
		//			$actualIsBillable = $a->isBillable();
		//			$checkIsBillable = $a->_computeIsBillable();
		//			if ($actualIsBillable !== $checkIsBillable) {
		//				$output = true;
		//				if ($checkIsBillable) {
		//					if ($a->member_is_empty("NextBillDate")) {
		//						echo "Account # " . $a->id() . " is not billed currently!\n";
		//						$output = false;
		//					}
		//				}
		//				if ($output) {
		//					$problem = ($checkIsBillable) ? " is not billable, should be billable" : "is billable, should *not* be billable";
		//					echo "Account # ". $a->id() . ": ". $a->memberGet("Name") . " $problem\n";
		//				}
		//				$x++;
		//			}
		//			$n++;
		//		}
		//		echo "$n Accounts checked, $x problems.\n";
		//	}
		//
		//	function taskRecomputeCommissions($id=false, $repair=false)
		//	{
		//		if ($id == "*") {
		//			$q = CModelClass::queryFromType("Account");
		//			$q->setWhere(array("IsReferrer" => true));
		//			$q->setWhat(array("ID","Name"));
		//			$q->iterateByRow();
		//			CLog::status("self::taskRecomputeCommissions($id): Can't find account");
		//			foreach ($q as $row) {
		//				CLog::status("##### Recomputing ".$row["Name"] . " (".$row["ID"].")");
		//				self::taskRecomputeCommissions($row["ID"],$repair);
		//			}
		//			return false;
		//		}
		//		$a = self::find($id);
		//		if (!$a instanceof Account) {
		//			$q = CModelClass::queryFromType("Account");
		//			$q->setWhere(array("IsReferrer" => true));
		//			$q->setWhat(array("ID","Name"));
		//			$q->iterateByRow();
		//			CLog::status("self::taskRecomputeCommissions($id): Can't find account");
		//			foreach ($q as $row) {
		//				CLog::status($row["Name"] . " (".$row["ID"].")");
		//			}
		//			return false;
		//		}
		//		// Get referred accounts
		//		$q = $a->newQuery();
		//		$q->addLink("AccountReferrer", "Referrer");
		//		$q->setWhere(array("AccountReferrer.Account" => $a->id(), "ManagerAccount" => $a->id()), "OR");
		//		$q->setDistinct();
		//		$q->setWhat(array("ID","Name"));
		//		$q->iterateByRow();
		//
		//		/*
		//		$n = 0;
		//		foreach ($q as $ref) {
		//		CLog::status("Referred account {$ref['Name']} ({$ref['ID']})");
		//		++$n;
		//		}
		//		*/
		//
		//		// Get mistakenly referred accounts
		//		$q = CModelClass::queryFromType("AccountTransaction");
		//		$q->addLink("Invoice");
		//		$q->addLink("Account", "Invoice.Account");
		//		$q->addLink("AccountReferrer", "Account.Referrer", "left");
		//		$q->setWhere(
		//		array(
		//		"Account.Referrer" => null,
		//		array(
				//		array(
						//		"AccountReferrer.Account:ne" => $a->id(),
						//		"AccountReferrer.Account" => null,
						//		),
		//		array(
				//		"Account.ManagerAccount:ne" => $a->id(),
				//		"Account.ManagerAccount" => null,
				//		),
						//		),
						//		),
		//		"OR"
						//		);
		//		$q->appendWhere(
		//		array(
		//		"AccountTransaction.Account" => $a->id(),
		//		"AccountTransaction.TransactionType" => "commission",
		//		)
		//		);
				//
				//		$q->setDistinct();
				//		//		echo $q->toSQL();
				//		foreach ($q as $ix) {
				//			CLog::status("$ix->ID: $ix->Amount, ".substr($ix->Description,0,20)." will be deleted");
				//			$ia = $ix->Invoice->Account;
				//			$r = $ia->Referrer;
				//			CLog::status("AID: http://cr.kent.acumen/manage/accounts/view/$ia->ID");
				//			if ($r instanceof AccountReferrer) {
				//				$refa = $r->Account;
				//				if ($refa) {
				//					$refaid = $refa->ID;
				//				} else {
				//					$refaid = "no account";
				//				}
				//				CLog::status("Actual referrer is $r->Name ($r->CodeName) ($refaid)");
				//			} else {
				//				CLog::status("No referrer");
				//			}
				//			if ($repair) {
				//				$ix->delete();
				//				CLog::status("======= DELETED");
				//			} else {
				//				CLog::status("======= NO ACTION TAKEN");
				//			}
				//		}
				//
				//		$q = CModelClass::queryFromType("AccountTransaction");
				//		$q->setWhere(
				//		array("Account" => $a->id(), "TransactionType" => "check")
				//		);
				//		$q->setWhat("EffectiveDate");
				//		$q->iterateByRow();
				//		$q->setLimit(1);
				//		$q->setOrderBy(array("-EffectiveDate","-OrderIndex"));
				//		$dateTime = $q->oneQueryDateTime("EffectiveDate");
				//		if (!$dateTime instanceof Timestamp) {
				//			$dateTime = $a->Created;
				//		}
				//		CLog::status("#### Recomputing starting from " .$dateTime->__toString());
				//		// Recompute invoice commissions
				//		$q = CModelClass::queryFromType("Invoice");
				//		$q->setWhat(array("Total" => "COUNT(Invoice.ID)"));
				//		$q->addLink("Account", "Account");
				//		$q->addLink("AccountReferrer", "Account.Referrer");
				//		$where = array();
				//		$where["AccountReferrer.Account"] = $a->id();
				//		if ($dateTime instanceof Timestamp) {
				//			$where["Invoice.BillDate:ge"] = $dateTime;
				//		}
				//		$q->setWhere($where);
				//		$q->setOrderBy("BillDate");
				//		$total = $q->oneQuery("Total");
				//
				//		$q->setWhat(false);
				//		CLog::status("$total invoices to recompute");
				//		foreach ($q as $inv) {
				//			CLog::status($inv->checkAccountCommissions($a, $repair));
				//		}
				//
				//		//echo "$n accounts referred.";
				//		return true;
				//	}
				//
				//	function widgetOneDotOh(/* CContext $context */)
				//	{
				//		$id = $this->id();
				//		$account = CCRAccountOLD::find($id);
				//		if (!$account) {
				//			return "No account found";
				//		}
				//		return $account->executeAction(kAction_View, $context, "", $account);
				//	}

				//	private function _widgetAccountMergeGo(/* CContext $context */, CTemplate $t)
				//	{
				//		$errors =
				//		array_merge(
				//		$this->_widgetAccountMergeAccounts($context, $t),
				//		$this->_widgetAccountMergeSites($context, $t),
				//		$this->_widgetAccountMergeUsers($context, $t)
				//		);
				//		return $errors;
				//	}

				//	private function _widgetAccountMergeAccounts(/* CContext $context */, CTemplate $t)
				//	{
				//		$errors = array();
				//
				//		$accounts = array_unique($context->getList("AddAccount", array()));
				//		$t->noblock(array("ActivatedSite","CancelledAccount","NoCancelAccount","AccountAddUser"));
				//		foreach ($accounts as $aid) {
				//			$t->noblock("PaymentByAccount");
				//			$a = self::find(intval($aid));
				//			if (!$a instanceof Account) {
				//				$errors[] = "Can't find account $aid";
				//				continue;
				//			}
				//			$t->set("Object", $a);
				//			$a->PaymentAccount = $this;
				//
				//			$theNextBillDate = $a->NextBillDate;
				//
				//			$nextBillDate = new Timestamp($this->NextBillDate);
				//			$nextBillDate->setTime(0,0,0);
				//			$nextBillDate->add(0,0,-1);
				//			while ($nextBillDate->before($theNextBillDate)) {
				//				$nextBillDate->add(0,1,0);
				//			}
				//			$a->NextBillDate = $nextBillDate;
				//
				//			$err = $a->store();
				//			if (is_error($err)) {
				//				$errors[] = $err;
				//			} else {
				//				$t->set("CurrentAccount", $a);
				//				$t->block("PaymentByAccount");
				//				$t->block("MergeStatus", true);
				//				if (CSiteConfig::getBoolean("Account", "SupportLegacy", false)) {
				//					CCRAccountOLD::grantAccount($this->id(), $a->id());
				//				}
				//			}
				//		}
				//		return $errors;
				//	}
				//
				//	private function _widgetAccountMergeSites(/* CContext $context */, CTemplate $t)
				//	{
				//		$errors = array();
				//
				//		$t->noblock(array("PaymentByAccount","AccountAddUser"));
				//		$sites = array_unique($context->getList("AddSite", array()));
				//		foreach ($sites as $sid) {
				//			$t->noblock(array("ActivatedSite","CancelledAccount","NoCancelAccount"));
				//			$s = CStatsSite::find(intval($sid));
				//			if (!$s instanceof CStatsSite) {
				//				$errors[] = "Can't find site $sid";
				//				continue;
				//			}
				//			$t->set("Object", $s);
				//			$lastAccount = $s->Account;
				//			if ($lastAccount->ID == $this->ID) {
				//				$errors[] = "Site $sid already assigned to account $this->ID";
				//				continue;
				//			}
				//			$activationDate = $lastAccount->LastBillDate;
				//			if (!$activationDate instanceof CModel || $activationDate->isEmpty()) {
				//				$activationDate = $s->Created;
				//			}
				//			if ($s->IsActive) {
				//				$s->Reactivated = $activationDate;
				//			}
				//			$s->Account = $this;
				//			$err = $s->store();
				//			$yep = false;
				//			if (is_error($err)) {
				//				$errors[] = $err;
				//			} else {
				//				$t->set("ActivatedSite", $s);
				//				$t->block("ActivatedSite");
				//				$yep = true;
				//				if (CSiteConfig::getBoolean("Account", "SupportLegacy", false)) {
				//					CCRAccountOLD::grantSite($this->id(), $s->id());
				//				}
				//			}
				//			$nSites = $lastAccount->memberGetInteger("Sites");
				//			$t->set("CurrentAccountSites", $nSites);
				//			if ($nSites == 0) {
				//				$err = $lastAccount->cancel();
				//				if (is_error($err)) {
				//					$errors[] = $err;
				//				} else {
				//					$t->set("CurrentAccount", $lastAccount);
				//					$t->block("CancelledAccount");
				//					$yep = true;
				//				}
				//			} else {
				//				$t->set("CurrentAccount", $lastAccount);
				//				$t->block("NoCancelAccount");
				//				$yep = true;
				//			}
				//			if ($yep) {
				//				$t->block("MergeStatus", true);
				//			}
				//		}
				//		return $errors;
				//	}
				//
				//	private function _widgetAccountMergeUsers(/* CContext $context */, CTemplate $t)
				//	{
				//		$errors = array();
				//		$t->noblock(array("ActivatedSite","CancelledAccount","NoCancelAccount","PaymentByAccount"));
				//		$users = array_unique($context->getList("AddUser", array()));
				//		foreach ($users as $uid) {
				//			$t->noblock("AccountAddUser");
				//			$u = CUser::find(intval($uid));
				//			if (!$u instanceof CUser) {
				//				$errors[] = "Can't find user $uid";
				//				continue;
				//			}
				//			$t->set("Object", $u);
				//			$t->set("Object.Name", $u->Login);
				//			$u->Accounts = $this;
				//			$err = $u->store();
				//			if (is_error($err)) {
				//				$errors[] = $err;
				//			} else {
				//				if (CSiteConfig::getBoolean("Account", "SupportLegacy", false)) {
				//					CCRAccountOLD::grantUser($this->id(), $u->id());
				//				}
				//				$t->set("CurrentAccount", $u);
				//				$t->block("AccountAddUser");
				//				$t->block("MergeStatus", true);
				//			}
				//		}
				//		return $errors;
				//	}

				//	function widgetAccountMerge(/* CContext $context */)
				//	{
				//		$t = $context->pushForm("AccountMerge");
				//		$doit = $context->get("Add");
				//		$errors = array();
				//		if ($doit) {
				//			$this->transactionStart();
				//			$errors = $this->_widgetAccountMergeGo($context, $t);
				//			$istest = $t->widgetModelGet("AddTest");
				//			$this->transactionEnd(!$istest);
				//		}
				//		$t->set("Account", $this);
				//		$t->errorBlock($errors);
				//		$search = $context->get("FindAccount");
				//		if ($search) {
				//			$t->set("FindAccount", $search);
				//
				//			$id = $this->id();
				//
				//			$q = $this->newQuery();
				//			$q->setSearch(array("*" => $search));
				//			$q->appendWhere(array("ID:ne" => $id));
				//			$q->setLimit(20);
				//			$t->blockMixed("FoundAccounts", $q);
				//
				//			$q = CModelClass::queryFromType("User");
				//			$q->setSearch(array("*" => $search));
				//			$q->appendWhere(array("User.Accounts.ID:ne" => $id));
				//			$q->setLimit(20);
				//			$t->blockMixed("FoundUsers", $q);
				//
				//			$q = CModelClass::queryFromType("StatsSite");
				//			$q->setSearch(array("*" => $search));
				//			$q->appendWhere(array("StatsSite.Account:ne" => $id));
				//			$q->setLimit(20);
				//			$t->blockMixed("FoundSites", $q);
				//		}
				//		return $context->popForm();
				//	}
				//
				//	function widgetAccountCredit(/* CContext $context */)
				//	{
				//		$t = $context->pushForm("AccountCredit");
				//		$t->set("Account", $this);
				//		return $context->popForm();
				//	}
				//
				//	function widgetCancel(/* CContext $context */)
				//	{
				//		$action = $context->parameterGet("CancelAction");
				//		if ($action == "uninstall-report") {
				//			// TODO
				//		}
				//		$t = $context->pushForm("AccountCancel");
				//
				//		$doit = $context->get("Cancel");
				//		$errors = array();
				//		$t->noblock(array("Cancelled", "CancellationDateError"));
				//		if ($doit) {
				//			$which = $context->get("CancellationDate");
				//			if (!empty($which)) {
				//				$theDate = ($which == "last") ? $this->LastBillDate : $this->NextBillDate;
				//				$this->transactionStart();
				//				$err = $this->cancel($theDate);
				//				$success = !is_error($err, true);
				//				$this->transactionEnd($success);
				//				if ($success) {
				//					$t->block("Cancelled");
				//				} else {
				//					$errors[] = $err;
				//				}
				//			} else {
				//				$t->block("CancellationDateError");
				//			}
				//		}
				//		$t->errorBlock($errors);
				//		$t->set("Account", $this);
				//		$t->errorBlock($errors);
				//		return $context->popForm();
				//	}


				/*
				 +-------+------+---------------------+---------------------+-------+---------------------------+
				 | ID    | Site | StartTime           | EndTime             | Total | Name                      |
				 +-------+------+---------------------+---------------------+-------+---------------------------+
				 | 16889 | 3270 | 2008-01-01 00:00:00 | 2008-01-31 23:59:59 |  NULL | Biomedical Structures     |
				 | 16918 | 3115 | 2008-01-01 00:00:00 | 2008-01-31 23:59:59 |  NULL | hab                       |
				 | 16919 | 3169 | 2008-01-01 00:00:00 | 2008-01-31 23:59:59 |  NULL | tcd                       |
				 | 16920 | 3170 | 2008-01-01 00:00:00 | 2008-01-31 23:59:59 |  NULL | wft                       |
				 | 16921 | 3177 | 2008-01-01 00:00:00 | 2008-01-31 23:59:59 |  NULL | ads                       |
				 | 16922 | 3178 | 2008-01-01 00:00:00 | 2008-01-31 23:59:59 |  NULL | vts                       |
				 | 16923 | 3223 | 2008-01-01 00:00:00 | 2008-01-31 23:59:59 |  NULL | taw                       |
				 | 16924 | 3240 | 2008-01-01 00:00:00 | 2008-01-31 23:59:59 |  NULL | sos                       |
				 | 16925 | 3251 | 2008-01-01 00:00:00 | 2008-01-31 23:59:59 |  NULL | fro                       |
				 | 16926 | 3252 | 2008-01-01 00:00:00 | 2008-01-31 23:59:59 |  NULL | ghi                       |
				 | 16927 | 3283 | 2008-01-10 10:14:20 | 2008-01-31 23:59:59 |  NULL | prm                       |
				 | 16928 | 3286 | 2008-01-11 15:49:02 | 2008-01-31 23:59:59 |  NULL | brs                       |
				 | 16929 | 3287 | 2008-01-11 15:57:09 | 2008-01-31 23:59:59 |  NULL | opm                       |
				 | 16964 | 2998 | 2008-01-02 00:00:00 | 2008-02-01 23:59:59 |  NULL | Merril RSG                |
				 | 16970 | 3134 | 2008-01-02 00:00:00 | 2008-02-01 23:59:59 |  NULL | Yankee Fundraising        |
				 | 16971 | 3194 | 2008-01-02 00:00:00 | 2008-02-01 23:59:59 |  NULL | JBPub                     |
				 | 16972 | 3195 | 2008-01-02 00:00:00 | 2008-02-01 23:59:59 |  NULL | Keurig                    |
				 | 16973 | 3222 | 2008-01-02 00:00:00 | 2008-02-01 23:59:59 |  NULL | Merrill TCS               |
				 | 16974 | 3226 | 2008-01-02 00:00:00 | 2008-02-01 23:59:59 |  NULL | Valudisplay               |
				 | 16975 | 3241 | 2008-01-02 00:00:00 | 2008-02-01 23:59:59 |  NULL | Practicon                 |
				 | 16976 | 3242 | 2008-01-02 00:00:00 | 2008-02-01 23:59:59 |  NULL | Bensonwood                |
				 | 16977 | 3250 | 2008-01-02 00:00:00 | 2008-02-01 23:59:59 |  NULL | Challah Connection        |
				 | 16978 | 3296 | 2008-02-01 12:35:25 | 2008-02-01 23:59:59 |  NULL | EatingWell                |
				 | 16993 | 3265 | 2008-01-06 00:00:00 | 2008-02-05 23:59:59 |  NULL | HerbalBuddy               |
				 | 17016 | 3120 | 2008-01-06 00:00:00 | 2008-02-05 23:59:59 |  NULL | Aquent                    |
				 | 17017 | 3176 | 2008-01-06 00:00:00 | 2008-02-05 23:59:59 |  NULL | LRN                       |
				 | 17018 | 3219 | 2008-01-06 00:00:00 | 2008-02-05 23:59:59 |  NULL | Custom Index              |
				 | 17019 | 3253 | 2008-01-06 00:00:00 | 2008-02-05 23:59:59 |  NULL | WashingtonSquareFinancial |
				 | 17020 | 3280 | 2008-01-07 13:30:11 | 2008-02-05 23:59:59 |  NULL | Aquent New                |
				 | 17021 | 3284 | 2008-01-10 11:31:57 | 2008-02-05 23:59:59 |  NULL | Aquent Creative           |
				 | 17022 | 3290 | 2008-01-17 11:35:03 | 2008-02-05 23:59:59 |  NULL | Zipcar                    |
				 | 17023 | 3291 | 2008-01-21 11:31:24 | 2008-02-05 23:59:59 |  NULL | Authoria                  |
				 | 17024 | 3292 | 2008-01-25 13:52:27 | 2008-02-05 23:59:59 |  NULL | AGI                       |
				 | 17045 | 3175 | 2008-01-09 00:00:00 | 2008-02-08 23:59:59 |  NULL | www.drawpro.com           |
				 | 17052 | 3130 | 2008-01-12 00:00:00 | 2008-02-11 23:59:59 |  NULL | Atxilipu                  |
				 | 17053 | 3145 | 2008-01-13 00:00:00 | 2008-02-12 23:59:59 |  NULL | www.stogea.com            |
				 | 17054 | 3149 | 2008-01-13 00:00:00 | 2008-02-12 23:59:59 |  NULL | Mediazione Linguistica    |
				 | 17055 | 3217 | 2008-01-13 00:00:00 | 2008-02-12 23:59:59 |  NULL | Esedra Formazione         |
				 | 17071 | 3228 | 2008-01-18 00:00:00 | 2008-02-17 23:59:59 |  NULL | www.netstandard.com       |
				 | 17072 | 3272 | 2008-01-18 00:00:00 | 2008-02-17 23:59:59 |  NULL | www.rubiconproject.com    |
				 +-------+------+---------------------+---------------------+-------+---------------------------+
				 */
