<?php

class Control_SortOrder extends Control {

	protected $options = array(
		'name' => 'so',
		'column' => 'sort_order'
	);
	private $ascending = null;
	private $list_default_ascending = null;

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

	public function hook_query_OLD(Database_Query_Select $query) {
		zesk::obsolete();
		$order_by_original = to_list($query->order_by(), array(), ",");
		$this->list_default_ascending = strtolower(str::right(first(to_list($query->order_by())), " ")) !== 'desc';
		if ($this->ascending === null) {
			$this->ascending = $this->list_default_ascending;
		}
		if ($this->ascending === $this->list_default_ascending) {
			return;
		}
		$order_by_reversed = array();
		foreach ($order_by_original as $token) {
			list($col, $ordering) = pair($token, " ", $token, "asc");
			$ordering = strtolower($ordering) === 'desc' ? "ASC" : "DESC";
			$order_by_reversed[] = "$col $ordering";
		}
		$query->order_by($order_by_reversed);
	}

	public function hook_after_query_list(Database_Query_Select $query) {
		$parser = $query->parser();
		$order_by_original = $parser->split_order_by($query->order_by());
		$this->list_default_ascending = !endsi(first($order_by_original), " desc");
		//		log::debug("Control_SortOrder: ascending=$this->ascending default=$this->list_default_ascending obo=" . _dump($order_by_original));
		if ($this->ascending === null) {
			//		log::debug("Control_SortOrder: this->ascending is null, setting to default $this->list_default_ascending");
			$this->ascending = $this->list_default_ascending;
		}
		if ($this->ascending === $this->list_default_ascending) {
			return;
		}
		$order_by_reversed = $parser->reverse_order_by($order_by_original);
		log::error($order_by_original);
		log::error($order_by_reversed);
		$query->order_by($order_by_reversed);
	}

	public function theme_variables() {
		return parent::theme_variables() + array(
			'ascending' => $this->ascending,
			'list_default_ascending' => $this->list_default_ascending,
			'uri' => $this->option('uri', $this->request->uri())
		);
	}
}