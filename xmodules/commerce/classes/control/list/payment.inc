<?php
/**
 * 
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class Control_List_Payment extends Control_List {
	protected $class = "Payment";
	protected $theme_widgets = "control/list/payment/widgets.tpl";
	private function _hook_query_common(Database_Query_Select $query) {
		$query->link("Payment_Type", array(
			"alias" => "T"
		));
		
		$user = $this->user();
		if (!$user->can("Payment::list_all")) {
			$where['X.owner'] = $user->id();
		}
	}
	function hook_query_list(Database_Query_Select $query) {
		$query->what_object();
		$query->what("*account_number_hidden", "CONCAT(LEFT(X.AccountNumber,4),' ... ',RIGHT(X.AccountNumber,4))");
		$query->what("payment_type_name", "T.name");
		$this->_hook_query_common($query);
	}
	function hook_query_total(Database_Query_Select $query) {
		$this->_hook_query_common($query);
	}
}
