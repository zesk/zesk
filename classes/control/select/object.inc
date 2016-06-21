<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/control/select/object.inc $
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 *            Created on Tue Jul 15 16:22:33 EDT 2008
 */
class Control_Select_Object extends Control_Select {
	/**
	 *
	 * @var string
	 */
	
	/**
	 *
	 * @var Class_Object
	 */
	protected $class_object = null;
	
	/**
	 * (non-PHPdoc)
	 * 
	 * @see Widget::model()
	 */
	protected function model() {
		$class = $this->class;
		if (empty($class)) {
			return parent::model();
		}
		return $this->application->object_factory($this->class);
	}
	protected function initialize() {
		if (empty($this->class)) {
			// Do not use "class" option - is also attribute on HTML tags. Use object_class
			$this->class = $this->option("object_class");
		}
		$class = $this->class_object = Object::cache_class($this->class, 'class');
		if (!$this->has_option('text_column')) {
			$this->set_option('text_column', $class->text_column);
		}
		parent::initialize();
	}
	protected function _where() {
		$where = $this->option("where", "");
		if (!is_array($where)) {
			return array();
		}
		if ($this->object) {
			$where = $this->object->apply_map($where);
		}
		return $where;
	}
	function value($set = null) {
		if ($set === "") {
			$this->object->set($this->column(), null);
			return $this;
		}
		return parent::value($set);
	}
	protected function id_column() {
		return $this->option('idcolumn', $this->class_object->id_column);
	}
	protected function text_columns() {
		$text_column = $this->option('text_column', $this->class_object->name_column);
		if (!$text_column) {
			$text_column = $this->class_object->name_column;
		}
		$text_column = to_list($text_column);
		$text_column = array_merge($text_column, $this->option_array('text_columns'));
		return $text_column;
	}
	protected function hook_options() {
		$db = $this->class_object->database();
		$query = Object::class_query($this->class);
		$prefix = $query->alias() . ".";
		
		$text_column = $this->text_columns();
		$what = arr::prefix(arr::flip_copy($text_column), $prefix);
		$query->what("id", $prefix . $this->class_object->id_column);
		$query->what($what, true);
		$query->order_by($this->option('order_by', $text_column));
		$query->where($this->_where());
		
		if (!$this->has_option('format')) {
			$this->set_option('format', implode(" ", arr::wrap(array_keys($what), '{', '}')));
		}
		$this->hook("options_query", $query);
		return $this->hook("options_query_format", $query);
	}
	protected function hook_options_query_format(Database_Query_Select $query) {
		$format = $this->option("format");
		$rows = $query->to_array("id");
		foreach ($rows as $key => $row) {
			$rows[$key] = map($format, $row);
		}
		if ($this->option_bool("translate_after")) {
			$rows = __($rows);
		}
		return $rows;
	}
	public function where($where = null, $append = false) {
		if ($where !== null) {
			if ($append) {
				if (is_array($where)) {
					$where = array(
						$where
					);
				}
				$where = $this->option_array("where", array()) + $where;
			}
			$this->set_option("where", $where);
			return $this;
		}
		return $this->option("where");
	}
	/**
	 *
	 * @deprecated 2015-01-12
	 * @see Widget::object_class
	 * @param string $set        	
	 * @return Control_Select_Object|string
	 */
	public function _class($set = null) {
		if ($set !== null) {
			$this->class = $set;
			$object = Object::cache_object($this->class);
			$this->set_option('table', $object->table());
			$this->set_option('idcolumn', $object->id_column());
			return $this;
		}
		return $this->class;
	}
}
