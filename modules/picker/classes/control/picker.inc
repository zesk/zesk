<?php
/**
 * @version $URL: https://code.marketacumen.com/zesk/trunk/modules/picker/classes/control/picker.inc $
 * @author $Author: kent $
 * @package {package}
 * @subpackage {subpackage}
 * @copyright Copyright (C) 2016, {company}. All rights reserved.
 */
use zesk\ArrayTools;
use zesk\HTML;
use zesk\URL;
use zesk\Database_Query_Select;
use zesk\ORMIterator;
use zesk\Control;

/**
 *
 * @author kent
 *
 */
class Control_Picker extends Control {

	/**
	 * Tag wrapped around all items
	 *
	 * @var string
	 */
	protected $list_tag = 'div';
	protected $list_attributes = ".control-picker-state";

	/**
	 * Tag wrapped around each item displayed
	 *
	 * @var string
	 */
	protected $item_tag = "li";

	/**
	 * Attributes for tag around each item displayed
	 * @var mixed
	 */
	protected $item_attributes = null;

	/**
	 * Renders an item in our list
	 *
	 * Defaults to
	 *
	 * subclass_class_name/item
	 * control/picker/item
	 *

	 * @var string
	 */
	protected $theme_item = null;

	/**
	 * Renders the actual selection tool for items
	 *
	 * subclass_class_name/selector
	 * control/picker/selector
	 *
	 * @var string
	 */
	protected $theme_item_selector = null;

	/**
	 * Renders the actual selection tool for items
	 *
	 * Defaults to
	 *
	 * subclass_class_name/search
	 * control/picker/search
	 *
	 * @var string
	 */
	protected $theme_item_search = null;
	protected $search_columns = array();

	/**
	 * Added to the form for the selector
	 *
	 * @var array
	 */
	public $form_attributes = array();

	/**
	 *
	 * @var Class_ORM
	 */
	protected $class_object = null;

	/**
	 * Data which should be passed to controller calls by the widget
	 *
	 * @var array
	 */
	protected $data_search = array();
	public function where(array $set = null) {
		return $set ? $this->set_option('where', $set) : $this->option_array('where');
	}
	public function data_search(array $set = null, $add = false) {
		if ($set !== null) {
			$this->data_search = $add ? $this->data_search + $set : $set;
		} else {
			return $this->data_search;
		}
	}
	public function picker_options(array $set = null, $add = true) {
		$options = to_array(avalue($this->theme_variables, 'picker_options', array()));
		if ($set !== null) {
			$this->theme_variables['picker_options'] = $add ? $set + $options : $set;
			return $this;
		}
		return $options;
	}
	protected function initialize() {
		$this->class = $this->application->objects->resolve($this->class);
		parent::initialize();
		$this->class_object = $this->application->class_orm_registry($this->class);
		if (count($this->search_columns) === 0) {
			$this->search_columns = array(
				$this->class_object->name_column
			);
		}
		if (!$this->has_option('order_by') && $this->class_object->name_column) {
			$this->set_option('order_by', "X." . $this->class_object->name_column);
		}
		$hierarchy = $this->application->classes->hierarchy($this, __CLASS__);
		if ($this->theme_item_selector === null) {
			$this->theme_item_selector = ArrayTools::suffix($hierarchy, '/selector');
		}
		if ($this->theme_item === null) {
			$this->theme_item = ArrayTools::suffix($hierarchy, '/item');
		}
		if ($this->theme_item_search === null) {
			$this->theme_item_search = ArrayTools::suffix($hierarchy, '/search');
		}
	}
	function inline_picker($set = null) {
		return $set !== null ? $this->set_option('inline_picker', to_bool($set)) : $this->option_bool('inline_picker');
	}
	function search_columns($set = null) {
		if ($set !== null) {
			$this->search_columns = to_list($set);
			return $this;
		}
		return $this->search_columns;
	}
	private function to_objects($value) {
		$value = to_iterator($value);
		if ($value instanceof ORMIterator) {
			return $value;
		}
		foreach ($value as $key => $id) {
			try {
				$value[$key] = $this->application->orm_factory($this->class, $id)->fetch();
			} catch (Exception $e) {
				unset($value[$key]);
			}
		}
		return $value;
	}
	public function selectable($set = null) {
		return $set === null ? $this->option_bool('selectable', true) : $this->set_option('selectable', to_bool($set));
	}
	public function hook_render() {
		if ($this->inline_picker()) {
			$this->theme = $this->theme_item_selector;
		}
	}
	public function single_item($set = null) {
		return $set === null ? $this->option_bool('single_item', $set) : $this->set_option('single_item', to_bool($set));
	}
	protected function load() {
		$object = $this->object;
		$input_name = $object->apply_map($this->name());
		if (!$this->request->has($input_name)) {
			$object->__set($this->column(), null);
		} else if ($this->single_item()) {
			$value = $this->request->get($this->name());
			if (is_array($value)) {
				$value = first($value);
			}
			$object->__set($this->column(), $value);
		} else {
			parent::load();
		}
	}
	public function object_class_css_class() {
		return strtr(strtolower($this->class), array(
			"\\" => "-",
			"_" => "-"
		));
	}
	public function theme_variables() {
		$locale = $this->application->locale;
		$class_object = $this->application->class_orm_registry($this->class);
		$name = $locale->lower($locale($class_object->name));
		$names = $locale->plural($name);
		return array(
			'title' => $this->option('title'),
			'target' => $this->inline_picker() ? null : $this->request->get('target'),
			'description' => $this->option('description'),
			'inline_picker' => $this->inline_picker(),
			'object_class' => $this->class,
			'object_class_css_class' => $this->object_class_css_class(),
			'class_object' => $class_object,
			'class_object_name' => $name,
			"class_object_names" => $names,
			'objects' => $this->to_objects($this->value()),
			'selectable' => $this->selectable(),
			'theme_item' => $this->theme_item,
			'item_tag' => $this->item_tag,
			'item_attributes' => $this->item_attributes,
			'list_tag' => $this->list_tag,
			'list_attributes' => $this->list_attributes,
			'data_search' => $this->data_search,
			'where' => $this->option_array('where'),
			'label_save' => $locale('Add selected {names}', array(
				'names' => $names
			)),
			'label_search' => $locale('Search {names}', array(
				"names" => $names
			))
		) + $this->options_include(array(
			'item_selector_none_selected',
			'item_selector_empty'
		)) + parent::theme_variables();
	}
	public function controller() {
		$action = $this->request->get('action');
		$variables = $this->theme_variables();
		if ($action === 'selector') {
			$content = $this->application->theme($this->theme_item_selector, array(
				"value" => $this->request->geta($this->column())
			) + $variables, array(
				"first" => true
			));
			$this->response->json(array(
				"content" => $this->wrap_form($content),
				"status" => true
			) + $this->response->to_json());
		} else if ($action === 'search') {
			$this->response->json($this->search_results($variables, $this->request->get("q")) + array(
				"class_object_name" => $variables['class_object_name'],
				"class_object_names" => $variables['class_object_names'],
				"status" => true
			) + $this->response->to_json());
		} else if ($action === "submit") {
			$this->response->json($this->submit_results($variables, $this->request->geta($this->column())) + array(
				"status" => true
			) + $this->response->to_json());
		}
		return null;
	}
	function submit_results(array $variables, array $ids) {
		$iter = $this->_query()->where("X." . $this->class_object->id_column, $ids)->orm_iterator();
		$content = "";
		foreach ($iter as $object) {
			$content .= $this->application->theme($this->theme_item, array(
				'object' => $object,
				"selected" => true,
				"column" => $this->column
			), array(
				"first" => true
			));
		}
		$this->response->javascript('/share/picker/js/picker.js', array(
			'share' => true
		));
		$this->response->jquery('$.picker();');
		return array(
			"status" => true,
			"message" => $this->option("submit_message"),
			"content" => $content
		);
	}
	function hook_query(zesk\Database_Query_Select $query) {
		$value = $this->request->get('q');
		if ($value === "" || $value === null) {
			return array();
		}
		$value = trim(preg_replace("/\s+/", " ", $value));
		$value = explode(" ", $value);
		$sql = $query->sql();
		$alias = $query->class_alias();
		$where = array();
		foreach ($this->search_columns as $col) {
			$where[$col . "|%|OR"] = $value;
		}
		$query->where(array(
			$where
		));
		$query->where($this->where());
		$query->condition($query->application->locale->__("match the string \"{q}\"", array(
			"q" => $value
		)));
	}
	private function _query() {
		return $this->application->orm_registry($this->class)
			->query_select()
			->what_object($this->class, "X")
			->limit(0, $this->option_integer('limit', 25))
			->order_by($this->option('order_by'));
	}
	private function search_results(array $variables, $q) {
		$query = $this->_query();
		$total = $this->application->orm_registry($this->class)->query_select();
		$this->call_hook('query_list;query', $query);
		$this->call_hook('query_total;query', $total);
		$total->what(array(
			"*total" => "COUNT(DISTINCT X." . $this->class_object->id_column . ")"
		));
		$results = array();
		foreach ($query->orm_iterator() as $id => $object) {
			$results[$id] = $this->application->theme($this->theme_item, array(
				"object" => $object,
				"id" => $id
			) + $variables, array(
				"first" => true
			));
		}
		$total = $total->one_integer("total");
		$result = array(
			"results" => $results,
			"total" => $total
		);
		if ($this->application->development()) {
			$result['query_sql'] = strval($query);
		}
		return $result;
	}
	private function wrap_form($content) {
		return HTML::tag('form', array(
			'method' => 'post',
			'class' => 'control-picker-selector',
			'action' => URL::query_remove($this->request->uri(), "widget::target;action;q")
		) + $this->form_attributes, $content . HTML::input_hidden("action", "submit") . HTML::input_hidden("widget::target", $this->request->get('widget::target')));
	}
}
