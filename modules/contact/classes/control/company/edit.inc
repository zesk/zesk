<?php
/**
 * $URL: http://code.marketacumen.com/zesk/trunk/modules/contact/classes/company.inc $
 * @package zesk
 * @subpackage contact
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 */
class Control_Company_Edit extends Control_Object_List {
	function _widgets() {
		$spec = array();

		$f = widgets::control_hidden('ID', 'ID');
		$spec[$f->column()] = $f;

		$f = widgets::control_text('Name', 'Name', true, 2, 64);
		$spec[$f->column()] = $f;

		$f = widgets::control_text('CodeName', 'CodeName', true, 2, 32);
		$spec[$f->column()] = $f;

		$f = widgets::control_textarea('Description', 'Description');
		$spec[$f->column()] = $f;

		//		$f = widgets::control_select_object('Logo','Logo');
		//		$f->set_option('table', 'TLogo');
		//		$spec[$f->column()] = $f;


		$f = widgets::control_text('TaxID', 'TaxID', false, -1, 64);
		$spec[$f->column()] = $f;

		$f = widgets::view_date('Created', 'Created');
		$spec[$f->column()] = $f;

		$f = widgets::view_date('Modified', 'Modified');
		$spec[$f->column()] = $f;

		return $spec;
	}
}

