<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/Control/Select/Object/Dynamic.php $
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 * Created on Tue Jul 15 16:22:33 EDT 2008
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class Control_Select_Object_Dynamic extends Control_Select_Object {
	/**
	 *
	 * @var Control
	 */
	private $control = null;
	
	/**
	 *
	 * @param unknown $object
	 * @return integer
	 */
	private function count_results() {
		$where = $this->_where();
		return $this->application->query_select($this->class)
			->what("*X", "COUNT(" . $this->id_column() . ")")
			->where($where)
			->one_integer("X");
	}
	
	/**
	 * 
	 * @param unknown $object
	 * @return string
	 */
	function ajaxCheck($object) {
		$col = $this->column();
		$query = trim($this->request->get($col . '_query', ''));
		if (empty($query)) {
			return json_encode(false);
		}
		$where = HTML::parse_attributes($this->option("where", ""));
		$where[$this->option("search_column", $this->option("text_column"))] = $query;
		$this->set_option("where", $where);
		$n_found = $this->count_results($object);
		if ($n_found > $this->optionSelectObjectsLimit()) {
			return json_encode($n_found);
		}
		$where = $this->_where($object);
		$options = $this->generateOptions($object);
		if (count($options) === 0) {
			$options = 0;
		}
		return json_encode($options);
	}
	
	/**
	 * 
	 * @return number
	 */
	function optionSelectObjectsLimit() {
		return $this->option_integer("select_objects_limit", 200);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Control_Select_ORM::initialize()
	 */
	public function initialize() {
		$this->control = new Control_Text($this->option());
		$this->child($this->control);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Control_Select::validate()
	 */
	function validate() {
		$this->control = null;
		$n_found = $this->count_results();
		if ($n_found > $this->optionSelectObjectsLimit()) {
			$this->response->jquery();
			$this->response->javascript("/share/zesk/js/zesk.js", array(
				'weight' => 'first'
			));
			$this->response->jquery('$(".control-select-object-dynamic .csod-button").keydown(Control_Select_Object_Dynamic_KeyDown);');
			return $this->control->validate();
		}
		return parent::validate();
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Widget::submit()
	 */
	function submit() {
		if ($this->control) {
			return $this->control->submit();
		}
		return parent::submit();
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Widget::render()
	 */
	function render() {
		if ($this->control) {
			$options = $this->option();
			$t = new Template($this->application, 'widgets/selectobjectdynamic/selectobjectdynamic.tpl', $options);
			return $t->render();
		}
		return parent::render();
	}
}
