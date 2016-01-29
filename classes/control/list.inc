<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/control/list.inc $
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 *            Created on Tue Jul 15 16:28:30 EDT 2008
 */
class Control_List extends Control_Widgets_Filter {
	protected $theme = "control/list";

	/**
	 * Class of the object we're listing.
	 *
	 * @var string
	 */
	protected $class = null;

	/**
	 *
	 * @var Class_Object
	 */
	protected $class_object = null;
	/**
	 * Options to create the object we're listing, per row
	 *
	 * @var string
	 */
	protected $class_options = null;

	/**
	 *
	 * @var Control_Pager
	 */
	protected $pager = null;

	/**
	 * Query
	 *
	 * @var Database_Query_Select
	 */
	protected $query = null;

	/**
	 * Total query
	 *
	 * @var Database_Query_Select
	 */
	protected $query_total = null;

	/**
	 * Theme when no content exists/filter is empty
	 *
	 * @var string
	 */
	protected $theme_empty = null;

	/**
	 * Header theme
	 *
	 * @var string
	 */
	protected $theme_prefix = null;

	/**
	 * Header theme
	 *
	 * @var string
	 */
	protected $theme_header = null;

	/**
	 * Main list content, iterates and creates rows
	 *
	 * @var string
	 */
	protected $theme_content = null;

	/**
	 * Row theme
	 *
	 * @var string
	 */
	protected $theme_row = null;

	/**
	 *
	 * @var string
	 */
	protected $theme_widgets = null;

	/**
	 * Footer theme
	 *
	 * @var string
	 */
	protected $theme_footer = null;
	/**
	 * Suffix theme
	 *
	 * @var string
	 */
	protected $theme_suffix = null;

	/**
	 * Row tag
	 */
	protected $list_tag = "div";
	/**
	 * Row attributes
	 *
	 * @var array
	 */
	protected $list_attributes = array(
		"class" => "list"
	);
	/**
	 * Row tag
	 */
	protected $row_tag = "div";
	/**
	 * Row attributes
	 *
	 * @var array
	 */
	protected $row_attributes = array(
		"class" => "row"
	);

	/**
	 *
	 * @var array
	 */
	protected $widgets = array();

	/**
	 * Cell tag
	 *
	 * @var array
	 */
	protected $widget_tag = "div";

	/**
	 * Cell attributes
	 *
	 * @var array
	 */
	protected $widget_attributes = array(
		"class" => "cell"
	);

	/**
	 * Total of list, cached
	 *
	 * @var integer
	 */
	protected $cache_total = null;

	/**
	 *
	 * @var boolean
	 */
	protected $query_hooked = false;

	/**
	 * Widget for row
	 *
	 * @var Control_Row
	 */
	protected $row_widget = null;

	/**
	 * Widgets to execute per-output row
	 * Deprecate this and place into row_widget, eventually
	 *
	 * @var array
	 */
	protected $row_widgets = array();

	/**
	 * Widgets to execute for header
	 *
	 * @var array
	 */
	protected $header_widgets = array();
	/**
	 * Widgets to execute for header
	 *
	 * @var Control_Header
	 */
	protected $header_widget = null;

	/**
	 * Widget which does a generic text search
	 *
	 * @var string
	 */
	protected $search_widget = null;

	/**
	 * Text search query
	 *
	 * @var string
	 */
	protected $search_query = null;
	public function where(array $where = null) {
		return $where === null ? $this->option_array('where') : $this->set_option('where', $where);
	}
	function model() {
		return new Model_List();
	}

	/**
	 * Set/get title
	 *
	 * @param string $set
	 * @return string Control_Edit
	 */
	public function title($set = null) {
		return $set === null ? $this->option('title') : $this->set_option('title', $set);
	}

	/**
	 *
	 * @param string $set
	 * @return Control_List|boolean
	 */
	public function show_pager($set = null) {
		return $set !== null ? $this->set_option('show_pager', to_bool($set)) : $this->option_bool('show_pager');
	}
	public function default_order_by($set = null) {
		return $set === null ? $this->option('list_default_order_by') : $this->set_option('list_default_order_by', $set);
	}
	protected function initialize() {
		//$this->object = Object::cache_object($this->class);
		$this->initialize_theme_paths();

		$this->class_object = Object::cache_class($this->class, "class");

		$this->row_widget = $row_widget = new Control_Row();
		$row_widget->names($this->name() . '_row');
		$row_widget->children($this->row_widgets = $this->hook_array('widgets', array(), array()));
		$this->child($row_widget);

		$this->hook("row_widget", $this->row_widget);

		$this->initialize_header_widgets();
		$this->initialize_query();
		$this->initialize_filter();

		if ($this->show_pager()) {
			$options = arr::kunprefix($this->options, "pager_", true);
			$this->pager = new Control_Pager();
			$this->child($this->pager);
			$this->children_hook('pager', $this->pager);
		}

		parent::initialize();
	}
	protected function initialize_query() {
		$this->query = $this->_query();
		if ($this->has_option('list_default_order_by', true)) {
			$this->query->order_by($this->option('list_default_order_by'));
		}
		$this->query_total = $this->_query_total();
		$this->query_hooked = false;
	}
	protected function initialize_pager() {
		if ($this->show_pager()) {
			$options = arr::kunprefix($this->options, "pager_", true);
			$this->pager = new Control_Pager();
			$this->child($this->pager);
			$this->children_hook('pager', $this->pager);
		}
	}

	/**
	 * Set up theme paths
	 *
	 * @return void
	 */
	protected function initialize_theme_paths() {
		$hierarchy = zesk::class_hierarchy($this, __CLASS__);
		foreach ($hierarchy as $index => $class) {
			$hierarchy[$index] = strtr(strtolower($class), "_", "/") . "/";
		}
		// Set default theme to control/foo/prefix, control/foo/header etc.
		// We do not initialize $theme_widgets as this is a special case, when initialized changes output behavior
		foreach (to_list("content;empty;footer;header;prefix;row;suffix;row") as $var) {
			$theme_var = "theme_$var";
			if (!$this->$theme_var) {
				$this->$theme_var = arr::suffix($hierarchy, $var);
			}
		}
	}
	private function initialize_header_widgets() {
		$this->header_widget = $header_widget = $this->widget_factory('Control_Header')->names($this->name() . '-header');
		$this->header_widgets = array();
		$included = to_list("list_order_column;show_size;list_order_variable;list_order_by;multisort;list_order_default_ascending;list_order_position;html;list_column_width;widget_save_result;context_class");
		foreach ($this->row_widgets as $widget) {
			/* @var $widget Widget */
			if ($widget->has_option("list_order_by", true)) {
				$w = $this->header_widgets[$widget->name()] = $this->widget_factory("Control_OrderBy", $widget->options_include($included))->names("" . $widget->name(), $widget->label());
			} else {
				$w = $this->header_widgets[$widget->name()] = $this->widget_factory('View_Text', $widget->options_include($included))->names($widget->name())->set_option("value", $widget->option("label_header", $widget->label()));
			}
			$header_widget->child($w);
		}
		$this->child($header_widget);
		$this->hook("header_widget", $header_widget);
	}

	private function _prepare_queries() {
		if (!$this->query_hooked) {
			$this->children_hook("before_query;before_query_list", $this->query);
			$this->children_hook("before_query;before_query_total", $this->query_total);

			$this->children_hook("query;query_list", $this->query);
			$this->children_hook("query;query_total", $this->query_total);

			$this->children_hook("after_query;after_query_list", $this->query);
			$this->children_hook("after_query;after_query_total", $this->query_total);
			$this->query_hooked = true;

			$this->cache_total = $this->total();
			$this->children_hook("total", $this->cache_total);
			if ($this->has_option('force_limit')) {
				$this->query->limit($this->option('force_limit'));
			}
		}
	}
	public function hook_render() {
		$this->_prepare_queries();
	}
	public function theme_variables() {
		$class_object = $this->class_object;
		return array(
			'query' => $this->query,
			'query_total' => $this->query_total,
			'total' => $this->cache_total,
			'render_header_widgets' => $this->option_bool('render_header_widgets', true),
			'header_widgets' => $this->header_widgets,
			'widgets' => $this->row_widgets,
			'hide_new_button' => $this->option_bool('hide_new_button'),
			'list_object_name' => __($class_object->name),
			'list_object_names' => lang::plural(__($class_object->name)),
			'list_class' => $this->class,
			'list_class_object' => $this->class_object,
			'pager' => $this->pager,
			'theme_prefix' => $this->theme_prefix,
			'theme_header' => $this->theme_header,
			'theme_content' => $this->theme_content,
			'theme_empty' => $this->theme_empty,
			'theme_row' => $this->theme_row,
			'theme_footer' => $this->theme_footer,
			'theme_suffix' => $this->theme_suffix,
			'list_tag' => $this->list_tag,
			'list_attributes' => $this->list_attributes,
			'row_tag' => $this->row_tag,
			'row_attributes' => $this->row_attributes,
			'widget_tag' => $this->widget_tag,
			'widget_attributes' => $this->widget_attributes,
			'theme_widgets' => $this->theme_widgets
		) + parent::theme_variables() + $this->options;
	}

	/**
	 * Retrieve select query for list
	 *
	 * @return Database_Query_Select
	 */
	private function _query() {
		$query = Object::class_query($this->class)->what_object($this->class);
		if ($this->has_option('where')) {
			$query->where($this->option_array('where'));
		}
		return $query;
	}

	/**
	 * Create the total query
	 *
	 * @param $where array
	 *        	Optional where expression
	 * @return Database_Query_Select
	 */
	private function _query_total() {
		$query = $this->_query();
		$query->what(array()); // Reset what
		$what = " COUNT(DISTINCT " . $this->list_what_default() . ")";
		return $query->what("*total", $what);
	}
	final protected function list_what_default() {
		$pk = $this->class_object->primary_keys;
		$what = $pk ? implode(",", arr::prefix($pk, "X.")) : "*";
		return $what;
	}

	/**
	 * Return total elements in this query
	 *
	 * @return integer
	 */
	final public function total() {
		return $this->query_total()->limit(0, 1)->one_integer("total", 0);
	}
	final public function force_limit($limit) {
		$this->set_option('force_limit', intval($limit));
		return $this;
	}
	/**
	 * Retrieve select query for list
	 *
	 * @return Database_Query_Select
	 */
	final function query() {
		$this->_prepare_queries();
		return $this->query;
	}

	/**
	 *
	 * @return Database_Query_Select
	 */
	final public function query_total() {
		$this->_prepare_queries();
		return $this->query_total;
	}

	public function submitted() {
		$name = $this->name();
		if ($name && $this->request->has($name)) {
			return true;
		}
		return $this->request->is_post();
	}

	public function hook_initialized() {
		if ($this->search_query === null) {
			return;
		}
		foreach ($this->filter->all_children() as $child) {
			/* @var $child Widget */
			if (!$child->has_option('search_default_value')) {
				continue;
			}
			$search_default_value = $child->option('search_default_value');
			$child->default_value($search_default_value);
		}
		if ($this->search_widget) {
			$this->child($this->search_widget)->default_value($this->search_query);
		}
	}
	public function search($query) {
		$this->search_query = $query;
		if (!$this->search_widget) {
			return array(
				'total' => 0,
				'shown' => 0
			);
		}
		$force_limit = $this->option_integer('search_limit', 10);
		$this->ready();
		$this->defaults();
		$this->initialize_query();
		$content = $this->force_limit($force_limit)->show_pager(false)->show_filter(false)->execute();
		$total = $this->total();
		return array(
			'content' => $content,
			'total' => $total,
			'shown' => min($total, $force_limit)
		);
	}
}

