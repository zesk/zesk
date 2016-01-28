<?php

class Control_Company_View {
	function _widgets() {
		$spec = array();

		$f = widgets::view_text('Name', 'Name');
		$spec[$f->column()] = $f;

		$f = widgets::view_text('CodeName', 'CodeName');
		$spec[$f->column()] = $f;

		$f = widgets::view_object('Logo', 'Logo');
		$f->set_option('table', 'TLogo');
		$spec[$f->column()] = $f;

		$f = widgets::view_text('TaxID', 'TaxID');
		$spec[$f->column()] = $f;

		$f = widgets::view_date('Created', 'Created');
		$spec[$f->column()] = $f;

		$f = widgets::view_date('Modified', 'Modified');
		$spec[$f->column()] = $f;

		$f = widgets::view_text('Description', 'Description');
		$spec[$f->column()] = $f;

		return $spec;
	}

}
