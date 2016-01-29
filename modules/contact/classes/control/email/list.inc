<?php

class Control_Contact_Email_List extends Control_Object_Edit {
	protected $class = "Control_Email";
	
	function listJoin() {
		$email_type_table = Object::class_table_name('Contact_Email_Type');
		return "LEFT OUTER JOIN $email_type_table T ON T.ID=X.EmailType";
	}
	
	function listWhat() {
		return lists::append('X.*', $this->option("list_what"), ',');
	}
	
	function listWidgetList() {
		$spec = $this->widget_factory('Control_Email_Edit')->widgets();
		foreach ($spec as $v) {
			$v->setInputName($v->name() . "[{ID}]");
		}
		$f = widgets::view_template("dhtml_actions.tpl", "Actions");
		$f->set_option("list_order_by", false);
		$spec[$f->column()] = $f;
		
		return $spec;
	}
}
	
