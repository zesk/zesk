<?php
/**
 * $URL: http://code.marketacumen.com/zesk/trunk/modules/contact/classes/contact/date.inc $
 * @package zesk
 * @subpackage default
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2006, Market Acumen, Inc.
 */

/**
 * Zesk_Contact_Date
 */
class Zesk_Contact_Date extends Contact_Info {

	function label_type() {
		return Contact_Label::LabelType_Date;
	}

	function listJoin() {
		$email_type_table = Object::class_table_name('Contact_Email_Type');
		return "LEFT OUTER JOIN $email_type_table T ON T.ID=X.EmailType";
	}

	function listWhat() {
		return lists::append('X.*', $this->option("list_what"), ',');
	}

	function listWidgetList() {
		$spec = $this->editWidgetList();
		foreach ($spec as $v) {
			$v->setInputName($v->name() . "[{ID}]");
		}
		$f = widgets::view_template("dhtml_actions.tpl", "Actions");
		$f->set_option("list_order_by", false);
		$spec[$f->column()] = $f;
		
		return $spec;
	}

	function newWidgetList() {
		$spec = $this->editWidgetList();
		return $spec;
	}

	function editWidgetList() {
		$spec = array();
		
		if ($this->option_bool("show_EmailType")) {
			$f = widgets::control_select("EmailType", "Type", _G("table_prefix") . "EmailType", true);
			$f->set_option('list_order_by', "T.Name");
			$f->set_option('order_by', "Name");
			$f->set_option("list_order_by_default", true);
			$f->set_option("onchange", "this.form.elements['OK[{ID}]'].disabled=false");
		} else {
			$f = widgets::control_hidden("EmailType");
		}
		//		$f->set_option("default", Email_Type::findID($this->option("default_email_type",'work')));
		$spec[$f->column()] = $f;
		
		$f = widgets::control_text("Name", "Name", true, 1, 128);
		$f->set_option("onchange", "this.form.elements['OK[{ID}]'].disabled=false");
		$spec[$f->column()] = $f;
		
		$f = widgets::control_email("Email", "Email", true, 1, 128);
		$f->set_option("onchange", "this.form.elements['OK[{ID}]'].disabled=false");
		$spec[$f->column()] = $f;
		
		return $spec;
	}
}
