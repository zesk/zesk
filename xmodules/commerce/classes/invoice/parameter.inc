<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/commerce/classes/invoice/parameter.inc $
 * @package zesk
 * @subpackage commerce
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
namespace zesk;

/**
 * Invoice_Parameter
 *
 * @package zesk
 * @subpackage commerce
 *            
 */
class Invoice_Parameter {
	private $Object;
	private $Product;
	private $Account_Transaction;
	private $Invoice_Item_Type;
	private $Description;
	private $Quantities;
	private $IsNew;
	
	/*====================================================================================*\
	 Instance
	 \*------------------------------------------------------------------------------------*/
	function __construct($object, $desc, $quantities = false) {
		$this->Object = $object;
		if (method_exists($object, "product")) {
			$this->Product = $object->product();
		} else {
			$this->Product = null;
		}
		$this->Account_Transaction = null;
		$this->Invoice_Item_Type = null;
		$this->Description = $desc;
		$this->Quantities = new Options($quantities);
		$this->IsNew = true;
	}
	
	/*====================================================================================*\
	 Get/Set
	 \*------------------------------------------------------------------------------------*/
	function object() {
		return $this->Object;
	}
	function objectID() {
		return $this->Object instanceof Object ? $this->Object->id() : null;
	}
	function product() {
		return $this->Product;
	}
	function productID() {
		return $this->Product instanceof Product ? $this->Product->id() : null;
	}
	function setTransaction($xaction) {
		$this->Account_Transaction = $xaction;
	}
	function transactionID() {
		return $this->Account_Transaction instanceof Account_Transaction ? $this->Account_Transaction->id() : null;
	}
	function transaction() {
		return $this->Account_Transaction;
	}
	function setItemType($mixed) {
		if ($mixed instanceof Invoice_Item_Type) {
			$this->Invoice_Item_Type = $mixed;
			return true;
		}
		$iit = new Invoice_Item_Type();
		if ($iit->find($mixed)) {
			$this->Invoice_Item_Type = $iit;
			return true;
		}
		throw new Invoice_Parameter_Exception("Unknown invoice item type: $mixed");
	}
	function itemType() {
		return $this->Invoice_Item_Type;
	}
	function itemTypeID() {
		return $this->Invoice_Item_Type instanceof Invoice_Item_Type ? $this->Invoice_Item_Type->id() : null;
	}
	function description() {
		return $this->Description;
	}
	function quantities() {
		return $this->Quantities;
	}
	function setIsNew($value = true) {
		$this->IsNew = $value ? true : false;
	}
	function is_newItem() {
		return $this->IsNew;
	}
}


