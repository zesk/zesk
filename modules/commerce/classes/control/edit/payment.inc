<?php

class Control_Edit_Payment extends Control_Edit {

	function newWidgetList() {
		$spec = $this->editWidgetList();
		unset($spec["Created"]);
		unset($spec["Modified"]);
		return $spec;
	}

	function filterWidgetList() {
		$spec = array();

		$f = Widget::factory("control_select")->names("a", "Active", array(
			"*" => "-- All --",
			"a" => "Active",
			"i" => "Inactive"
		));
		$f->setRequired();
		$f->set_option("Default", "a");
		$f->set_option("DynamicColumn", "a");
		$dynOptions = array(
			"*" => array(
				"where" => array()
			),
			"a" => array(
				"where" => array(
					"X.Deactivated" => null
				)
			),
			"i" => array(
				"where" => array(
					"*X.Deactivated|!=" => null
				)
			)
		);
		$f->set_option("DynamicOptions", $dynOptions);
		$f->set_option("refresh", true);
		$spec[] = $f;

		$f = Widget::factory("control_text")->names("find", null, false);
		$f->set_option("where_column", array(
			"X.Name|%",
			"P.Name|%",
			"X.URL|%"
		));
		$but = Widget::factory("control_button")->names("", "", "Search");
		$f->set_option("suffix", $but->output(array()));
		$spec[] = $f;

		return $spec;
	}

	function _widgets() {

		$ww = array();

		$ww[] = $w = Widget::factory("control_textarea")->names('Name', 'Name');

		$ww[] = $w = Widget::factory("control_textarea")->names('Description', 'Description');

		$ww[] = $w = Widget::factory("control_select_object")->names('PaymentType', 'PaymentType');
		$w->set_option('table', 'TPaymentType');

		$ww[] = $w = Widget::factory("control_textarea")->names('OwnerName', 'OwnerName');

		$ww[] = $w = Widget::factory("control_textarea")->names('AccountNumber', 'AccountNumber');

		$ww[] = $w = Widget::factory("control_textarea")->names('SecurityCode', 'SecurityCode');

		$ww[] = $w = Widget::factory("control_select_object")->names('Address', 'Address');
		$w->set_option('table', 'TAddress');

		$ww[] = $w = Widget::factory("control_date")->names('ExpireDate', 'ExpireDate');

		$ww[] = $w = Widget::factory("control_date")->names('Created', 'Created');

		$ww[] = $w = Widget::factory("View_Date")->names('Modified', 'Modified');

		$ww[] = $w = Widget::factory("View_Date")->names('Validated', 'Validated');

		$ww[] = $w = Widget::factory("control_checkbox")->names('IsValid', 'IsValid');

		$ww[] = $w = Widget::factory("control_textarea")->names('AVSCode', 'AVSCode');

		$ww[] = $w = Widget::factory("Control_Email")->names('Email', 'Email');

		$ww[] = $w = Widget::factory("View_Date")->names('Declined', 'Declined');

		$ww[] = $w = Widget::factory("Control_User")->names('owner', 'owner');

		$ww[] = $w = Widget::factory("control_select_object")->names('Account', 'Account');
		$w->set_option('table', 'TAccount');

		$ww[] = $w = Widget::factory("control_date")->names('DeclineNotified', 'DeclineNotified');

		$ww[] = $w = Widget::factory("control_select_object")->names('DeclineNotifyCount', 'DeclineNotifyCount');
		$w->set_option('table', 'TDeclineNotifyCount');

		return $ww;
	}
}
