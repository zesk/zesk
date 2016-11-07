<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/contact/classes/contact/phone/areacode.inc $
 * @package zesk
 * @subpackage contact
 * @author kent
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */

/**
 * Contact_Phone_AreaCode
 */
class Contact_Phone_AreaCode extends Object {
	function editWidgetList() {
		$spec = array();
		
		$f = widgets::control_integer('Code', 'Code');
		$spec[$f->column()] = $f;
		
		$f = Province::control('Province', 'State/Province');
		$spec[$f->column()] = $f;
		
		$f = widgets::control_textarea('Description', 'Description');
		$spec[$f->column()] = $f;
		
		return $spec;
	}
	function viewWidgetList() {
		$spec = array();
		
		$f = widgets::view_integer('Code', 'Code');
		$spec[$f->column()] = $f;
		
		$f = Province::view('Province', 'State/Province');
		$spec[$f->column()] = $f;
		
		$f = widgets::view_textarea('Description', 'Description');
		$spec[$f->column()] = $f;
		
		return $spec;
	}
}

/**
 * Class_Contact_Phone_AreaCode
 */
class Class_Contact_Phone_AreaCode extends zesk\Class_Object {
	public $find_keys = array(
		"Code"
	);
}
