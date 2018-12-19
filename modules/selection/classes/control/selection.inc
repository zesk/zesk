<?php
/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Control_Selection extends Control {
	/**
	 *
	 * @var Selection_Type
	 */
	private $type = null;

	/**
	 * Get/set class associated with this selection
	 *
	 * @param string $set
	 * @return string|Control_Selection
	 */
	public function selection_class($set = null) {
		return ($set === null) ? $this->option('selection_class') : $this->set_option('selection_class', $set);
	}

	/**
	 * Get/Set code used to find this selection
	 *
	 * @param string $set
	 * @return string|Control_Selection
	 */
	public function selection_code($set = null) {
		return ($set === null) ? $this->option('selection_code', $this->selection_class()) : $this->set_option('selection_code', $set);
	}

	/**
	 *
	 * @return Selection_Type
	 */
	protected function selection_type() {
		if ($this->type) {
			return $this->type;
		}
		return $this->type = $this->application->orm_factory(Selection_Type::class, array(
			"session" => $this->session(),
			"user" => $this->user(),
			"code" => $this->selection_code(),
			"class" => $this->selection_class(),
		))->register();
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see Widget::initialize()
	 */
	protected function initialize() {
		$class = $this->selection_class();
		if (empty($class)) {
			$current = $this;
			while (($current = $current->parent()) !== null) {
				$class = $current->object_class();
				if ($class) {
					$this->selection_class($class);
					break;
				}
			}
		}
		if (empty($class)) {
			throw new Exception_Semantics("{class} needs selection class set before usage", array(
				"this" => $this,
				"class" => get_class($this),
			));
		}
		parent::initialize();
	}

	/**
	 * Update selected class attribute in list rows
	 *
	 * @param Model $object
	 * @param Control_Row $row
	 * @param zesk\Template $t
	 */
	public function hook_control_list_row(Model $object, Control_Row $row, Template $t) {
		if (!$object instanceof Object) {
			return;
		}
		if ($object->member_boolean("selected")) {
			$row->row_attributes(HTML::add_class($row->row_attributes(), "selected"));
		} else {
			$row->row_attributes(HTML::remove_class($row->row_attributes(), "selected"));
		}
	}

	/**
	 * Retrieve queries from the Control_List
	 *
	 * @retrun array
	 */
	public function retrieve_queries() {
		$top = $this->top();
		if ($top instanceof Control_List) {
			return array(
				$top->query_total(),
				$top->query(),
			);
		}
		$this->application->logger->error("{class}: Top widget is not a list: {instead}", array(
			"class" => get_class($this),
			"instead" => get_class($top),
		));
		return array(
			null,
			null,
		);
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Widget::controller()
	 */
	public function controller() {
		$request = $this->request();
		$response = $this->response();
		if (!$this->application->development() && !$request->is_post()) {
			$response->json(array(
				"status" => false,
				"message" => "Not a POST",
			));
			return;
		}
		$status = true;
		$result = array();
		$type = $this->selection_type();
		$already_cleared = false;
		switch ($action = $request->get("action")) {
			case "none":
				$message = __("Selection deleted.");
				$type->items_clear();
				$already_cleared = true;
				break;
			case "add-all":
				$message = __("Selection expanded.");
				list($query_total, $query_list) = $this->retrieve_queries();
				if (!$query_total) {
					$status = false;
					$message = __("Unable to retrieve queries");
				} else {
					$type->items_all($query_total, $query_list, true);
					$message = __("Added to current selection.");
				}
				break;
			case "remove-all":
				$message = __("Selection reduced.");
				list($query_total, $query_list) = $this->retrieve_queries();
				if (!$query_total) {
					$status = false;
					$message = __("Unable to retrieve queries");
				} else {
					$type->items_all($query_total, $query_list, false);
					$message = __("Reduced current selection.");
				}
				break;
			default:
				$message = null; //__("Updated selection");
				$add = $request->geta("add");
				$remove = $request->geta("remove");
				$type->items_update($add, $remove);
				$result += array(
					"added" => count($add),
					"removed" => count($remove),
				);
				break;
		}
		// If the list has had everything removed from it, then just clear the list if it hasn't been cleared above already
		$count = $type->count();
		if ($count === 0 && !$already_cleared) {
			$type->items_clear();
		}
		$response->json($result + array(
			"status" => $status,
			"message" => $message,
			"count" => $count,
		));
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Widget::theme_variables()
	 */
	public function theme_variables() {
		$type = $this->selection_type();
		return parent::theme_variables() + array(
			'target' => $this->option('target', '.row'),
			'container' => $this->option('container', '.list'),
			'selection_type' => $type,
			'count' => $type->count(),
			'actions' => $this->option_array("actions", array()),
		);
	}

	/**
	 *
	 * @param Database_Query_Select $query
	 */
	protected function hook_query_list(Database_Query_Select $query) {
		if ($query->has_what("selected")) {
			// TODO Why is this called twice while listing?
			return;
		}
		$type_id = $this->selection_type()->id();
		$alias = $query->alias();
		$id_column = $this->application->orm_registry($query->orm_class())->id_column();
		$table = $this->application->orm_registry(__NAMESPACE__ . "\\Selection_Item")->table();
		$query->join("LEFT OUTER JOIN $table sel_item ON sel_item.type=$type_id AND sel_item.id=$alias.$id_column");
		$query->what("*selected", "sel_item.add");
	}
}
