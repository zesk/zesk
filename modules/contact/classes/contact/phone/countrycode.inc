<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/contact/classes/contact/phone/countrycode.inc $
 * @package zesk
 * @subpackage contact
 * @author kent
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
class Contact_Phone_CountryCode extends Object {
	function editWidgetList() {
		$spec = array();
		
		$f = widgets::control_integer('Code', 'Code');
		$spec[$f->column()] = $f;
		
		$f = Contact_Phone_AreaCode::control('AreaCode', 'Area Code');
		$spec[$f->column()] = $f;
		
		$f = Country::control('Country', 'Country');
		$spec[$f->column()] = $f;
		
		$f = widgets::control_text('GlobalName', 'Global Name');
		$spec[$f->column()] = $f;
		
		return $spec;
	}
	function viewWidgetList() {
		$spec = array();
		
		$f = widgets::view_integer('Code', 'Code');
		$f->set_option('table', 'TCode');
		$spec[$f->column()] = $f;
		
		$f = Contact_Phone_AreaCode::view('AreaCode', 'Area Code');
		$spec[$f->column()] = $f;
		
		$f = Country::view('Country', 'Country');
		$spec[$f->column()] = $f;
		
		$f = widgets::view_text('GlobalName', 'Global Name');
		$spec[$f->column()] = $f;
		
		return $spec;
	}
}
class Class_Contact_Phone_CountryCode extends Class_Object {}
