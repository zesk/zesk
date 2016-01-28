<?php
/**
* $URL: https://code.marketacumen.com/zesk/trunk/modules/commerce/classes/product.inc $
* @package zesk
* @subpackage objects
*/
class Product extends Object {

	function listWidgetList() {
		$user = User::master();
		$factory = WidgetFactory::master();
		$spec = array();
		
		$f = $factory->newViewText('Name');
		$spec[$f->column()] = $f;
		
		$f = $factory->newActions();
		$spec[$f->column()] = $f;
		
		return $spec;
	}

	function editWidgetList() {
		$user = User::master();
		$factory = WidgetFactory::master();
		$spec = array();
		$f = $factory->newControlHidden('ID', 'ID');
		$spec[$f->column()] = $f;
		
		$f = $factory->newControlTextArea('CodeName', 'CodeName');
		$spec[$f->column()] = $f;
		
		$f = $factory->newControlTextArea('Name', 'Name');
		$spec[$f->column()] = $f;
		
		$f = $factory->newControlTextArea('Description', 'Description');
		$spec[$f->column()] = $f;
		
		$f = $factory->newControlCheckbox('HasCommission', 'HasCommission');
		$spec[$f->column()] = $f;
		
		$f = $factory->newControlSelectObject('Site', 'Site');
		$f->set_option('table', 'TSite');
		$spec[$f->column()] = $f;
		
		$f = $factory->newControlTextArea('ModelType', 'ModelType');
		$spec[$f->column()] = $f;
		
		$f = $factory->newControlTextArea('PricingClass', 'PricingClass');
		$spec[$f->column()] = $f;
		
		return $spec;
	}

	function viewWidgetList() {
		$user = User::master();
		$factory = WidgetFactory::master();
		$spec = array();
		$f = $factory->newViewText('CodeName', 'CodeName');
		$spec[$f->column()] = $f;
		
		$f = $factory->newViewText('Name', 'Name');
		$spec[$f->column()] = $f;
		
		$f = $factory->newViewText('Description', 'Description');
		$spec[$f->column()] = $f;
		
		$f = $factory->newViewCheckbox('HasCommission', 'HasCommission');
		$spec[$f->column()] = $f;
		
		$f = $factory->newViewObject('Site', 'Site');
		$f->set_option('table', 'TSite');
		$spec[$f->column()] = $f;
		
		$f = $factory->newViewText('ModelType', 'ModelType');
		$spec[$f->column()] = $f;
		
		$f = $factory->newViewText('PricingClass', 'PricingClass');
		$spec[$f->column()] = $f;
		
		return $spec;
	}
}

