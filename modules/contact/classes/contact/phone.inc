<?php
/**
* $URL: https://code.marketacumen.com/zesk/trunk/modules/contact/classes/contact/phone.inc $
* @package zesk
* @subpackage objects
*/
class Contact_Phone extends Contact_Info {
	function label_type() {
		return Contact_Label::LabelType_Phone;
	}
	/*
	function editWidgetList()
	{
		$f = Contact_Phone_Type::control('Contact_Phone_Type','Type');
		$spec[$f->column()] = $f;

		$f = widgets::control_phone('Number','Number');
		$spec[$f->column()] = $f;

		$f = widgets::control_text('Extension','Extension');
		$spec[$f->column()] = $f;

		return $spec;
	}

	function viewWidgetList()
	{
		$spec = array();

		$f = Contact_Phone_Type::view('Contact_Phone_Type','Type');
		$spec[$f->column()] = $f;

		$f = widgets::view_text('Number','Number');
		$spec[$f->column()] = $f;

		$f = widgets::view_text('Extension','Extension');
		$spec[$f->column()] = $f;

		return $spec;
	}
*/
}
