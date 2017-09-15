<?php
class Module_Commerce extends zesk\Module {
	protected $classes = array(
		"Invoice",
		"Invoice_Payment",
		"Invoice_Item",
		"Invoice_Item_Type",
		"Account",
		"Account_Transaction",
		"Account_Transaction_Type",
		"Payment",
		"Payment_Transaction"
	);
}
