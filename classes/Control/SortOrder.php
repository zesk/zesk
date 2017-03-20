<?php

namespace zesk;

class Control_SortOrder extends Control {
	protected $options = array(
		'name' => 'so',
		'column' => 'sort_order'
	);
	
	/**
	 * 
	 * @var boolean
	 */
	private $ascending = null;
	
	/**
	 * 
	 * @var boolean
	 */
	private $list_default_ascending = null;
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Widget::initialize()
	 */
	public function initialize() {
		parent::initialize();
		$name = $this->name();
		if ($this->request->has($name)) {
			$this->ascending = $this->request->get($name) === "asc";
		}
		if ($this->parent instanceof Control_Pager) {
			$this->parent->preserve_hidden("so", $this->default_value());
		}
	}
	
	/**
	 * 
	 * @param Database_Query_Select $query
	 */
	public function hook_after_query_list(Database_Query_Select $query) {
		$parser = $query->parser();
		$order_by_original = $parser->split_order_by($query->order_by());
		$this->list_default_ascending = !endsi(first($order_by_original), " desc");
		if ($this->ascending === null) {
			$this->ascending = $this->list_default_ascending;
		}
		if ($this->ascending === $this->list_default_ascending) {
			return;
		}
		$order_by_reversed = $parser->reverse_order_by($order_by_original);
		$query->order_by($order_by_reversed);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Widget::theme_variables()
	 */
	public function theme_variables() {
		return parent::theme_variables() + array(
			'ascending' => $this->ascending,
			'list_default_ascending' => $this->list_default_ascending,
			'uri' => $this->option('uri', $this->request->uri())
		);
	}
}
