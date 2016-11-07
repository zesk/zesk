<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/control/select/object/hierarchy.inc $
 *
 * @package zesk
 * @subpackage widgets
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 */
class Control_Select_Object_Hierarchy extends Control_Select {
	public $class = null;
	/**
	 *
	 * @var Class_Object
	 */
	public $class_object = null;
	function __construct($options = false) {
		parent::__construct($options);
		$this->options["novalue"] = 0;
	}
	protected function initialize() {
		if ($this->class === null) {
			$this->class = $this->option('class', null);
			if ($this->class === null) {
				throw new Exception_Semantics("Need to define class in {class}", array(
					"class" => get_class($this)
				));
			}
		}
		$this->class_object = Object::cache_class($this->class, "class");
	}
	private function id_column() {
		$idcolumn = $this->option("idcolumn", $this->class_object->id_column);
		return $idcolumn;
	}
	private function text_column() {
		$textcolumn = $this->option("textcolumn", $this->class_object->name_column);
		return $textcolumn;
	}
	private function parent_column() {
		$parentcolumn = $this->option("parentcolumn", $this->class_object->option('column_parent', 'parent'));
		return $parentcolumn;
	}
	private function select_map(array $where) {
		$idcolumn = $this->id_column();
		$textcolumn = $this->text_column();
		$parentcolumn = $this->parent_column();

		$order_by = $this->option("order_by", $textcolumn);

		$map = Object::class_query($this->class)->what(array(
			"id" => $idcolumn,
			"parent" => $parentcolumn,
			"name" => $textcolumn
		))->where($where)->order_by($order_by)->to_array();

		return $map;
	}
	function hook_options() {
		$where = HTML::parse_attributes($this->option("where", ""));
		$where = map($where, HTML::parse_attributes($this->option("default_map", "")));

		$idcolumn = $this->id_column();
		$textcolumn = $this->text_column();
		$parentcolumn = $this->parent_column();

		$map = $this->select_map($where);

		$format = $this->option("Format", "{" . $textcolumn . "}");

		$options = array();
		foreach ($map as $row) {
			$optgroup_name = map($format, $row);
			$where[$parentcolumn] = $row[$idcolumn];
			$options[$optgroup_name] = $map = $this->select_map($where);
		}
		$this->options["options"] = $options;
		$this->options["optgroup"] = true;

		return $options;
	}
}
