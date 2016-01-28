<?php
/**
 * $URL$
 * @package zesk
 * @subpackage content
 * @author kent
 * @copyright Copyright &copy; 2012, Market Acumen, Inc.
 */
class Control_Content_Menu_Tree extends Control_Object_List_Tree {

	protected $class = "Content_Menu";

	public function _query() {
		$query = parent::_query();

		return $query;
	}

	protected function _widgets() {
		$spec = array();
		$spec[] = widgets::control_text("Name");
		$spec[] = widgets::actions("{Name}");
		return $spec;
	}
}
