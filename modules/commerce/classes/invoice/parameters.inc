<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/commerce/classes/invoice/parameters.inc $
 * @package zesk
 * @subpackage commerce
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 */
namespace zesk;

/**
 * InvoiceParameters
 *
 * Big object to group parameters, invoice messages, and aid in
 * bill creation.
 *
 * @package zesk
 * @subpackage commerce
 *            
 */
class Invoice_Parameters extends Options {
	protected $Account;
	protected $Parameters;
	protected $Messages;
	public $PrevDate;
	public $BillDate;
	public $NextDate;
	function __construct(Account $account, Timestamp $billDate, $forceBillUnit = true) {
		assert('!$billDate->isEmpty()');
		assert('$account instanceof Account');
		
		$this->Account = $account;
		$this->Invoice = null;
		
		$this->PrevDate = $account->lastInvoiceDate($billDate, true);
		if ($this->PrevDate->isEmpty() && $forceBillUnit) {
			list($units, $unitsCount) = $account->billTimeUnits();
			$this->PrevDate->set($billDate);
			$this->PrevDate->addUnit($units, -$unitsCount);
		}
		$this->BillDate = $billDate;
		$this->NextDate = $account->nextBillDateFrom($billDate);
		$this->NextDate = $this->NextDate->earlier($account->Cancelled);
		
		assert('$this->PrevDate->isEmpty() || $this->PrevDate->before($billDate)');
		assert('$this->NextDate->isEmpty() || $this->NextDate->after($billDate, true)');
		
		$this->Parameters = array();
		$this->Messages = array();
		$this->Discount = 0.0;
		$this->DiscountMessages = array();
	}
	
	/*====================================================================================*\
	 Get/Set
	 \*------------------------------------------------------------------------------------*/
	function account() {
		return $this->Account;
	}
	function setInvoice(Invoice $invoice) {
		$this->Invoice = $invoice;
	}
	function invoice() {
		return $this->Invoice;
	}
	function previousDate() {
		return $this->PrevDate;
	}
	function billDate() {
		return $this->BillDate;
	}
	function nextDate() {
		return $this->NextDate;
	}
	function parametersEmpty() {
		$this->Parameters = array();
	}
	function parameterAdd(Invoice_Parameter $param) {
		$this->Parameters[] = $param;
		return true;
	}
	function parameters() {
		return $this->Parameters;
	}
	function messageAdd($message) {
		if (is_array($message)) {
			$this->Messages = array_merge($this->Messages, $message);
		} else {
			$this->Messages[] = strval($message);
		}
		return true;
	}
	function discountAdd($amount, $message = "") {
		if ($amount == 0) {
			return;
		}
		$this->Discount += $amount;
		if ($message) {
			if (is_array($message)) {
				$this->DiscountMessages = array_merge($this->DiscountMessages, $message);
			} else if (is_string($message)) {
				$this->DiscountMessages[] = $message;
			}
		}
	}
	function discount() {
		return $this->Discount;
	}
	function messages() {
		return implode("\n", $this->Messages);
	}
	function discountMessages() {
		return implode("\n", $this->DiscountMessages);
	}
}

