<?php

class Class_Invoice_Payment extends zesk\Class_Object {
	public $id_column = "id";

	public $has_one = array(
		"invoice" => "Invoice",
		"payment_transaction" => "Payment_Transaction"
	);
}
