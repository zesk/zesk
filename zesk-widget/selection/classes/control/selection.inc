<?php declare(strict_types=1);
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
		return ($set === null) ? $this->option('selection_class') : $this->setOption('selection_class', $set);
	}

	/**
	 * Get/Set code used to find this selection
	 *
	 * @param string $set
	 * @return string|Control_Selection
	 */
	public function selection_code($set = null) {
		return ($set === null) ? $this->option('selection_code', $this->selection_class()) : $this->setOption('selection_code', $set);
	}

	/**
	 *
	 * @return Selection_Type
	 */
	protected function selection_type() {
		if ($this->type) {
			return $this->type;
		}
		return $this->type = $this->application->ormFactory(Selection_Type::class, [
			'session' => $this->session(),
			'user' => $this->user(),
			'code' => $this->selection_code(),
			'class' => $this->selection_class(),
		])->register();
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see Widget::initialize()
	 */
	protected function initialize(): void {
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
			throw new Exception_Semantics('{class} needs selection class set before usage', [
				'this' => $this,
				'class' => get_class($this),
			]);
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
	public function hook_control_list_row(Model $object, Control_Row $row, Template $t): void {
		if (!$object instanceof Object) {
			return;
		}
		if ($object->member_boolean('selected')) {
			$row->row_attributes(HTML::addClass($row->row_attributes(), 'selected'));
		} else {
			$row->row_attributes(HTML::removeClass($row->row_attributes(), 'selected'));
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
			return [
				$top->query_total(),
				$top->query(),
			];
		}
		$this->application->logger->error('{class}: Top widget is not a list: {instead}', [
			'class' => get_class($this),
			'instead' => $top::class,
		]);
		return [
			null,
			null,
		];
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Widget::controller()
	 */
	public function controller(): void {
		$request = $this->request();
		$response = $this->response();
		if (!$this->application->development() && !$request->isPost()) {
			$response->json()->setData([
				'status' => false,
				'message' => 'Not a POST',
			]);
			return;
		}
		$status = true;
		$result = [];
		$type = $this->selection_type();
		$already_cleared = false;
		switch ($action = $request->get('action')) {
			case 'none':
				$message = __('Selection deleted.');
				$type->items_clear();
				$already_cleared = true;
				break;
			case 'add-all':
				$message = __('Selection expanded.');
				[$query_total, $query_list] = $this->retrieve_queries();
				if (!$query_total) {
					$status = false;
					$message = __('Unable to retrieve queries');
				} else {
					$type->items_all($query_total, $query_list, true);
					$message = __('Added to current selection.');
				}
				break;
			case 'remove-all':
				$message = __('Selection reduced.');
				[$query_total, $query_list] = $this->retrieve_queries();
				if (!$query_total) {
					$status = false;
					$message = __('Unable to retrieve queries');
				} else {
					$type->items_all($query_total, $query_list, false);
					$message = __('Reduced current selection.');
				}
				break;
			default:
				$message = null; //__("Updated selection");
				$add = $request->getList('add');
				$remove = $request->getList('remove');
				$type->items_update($add, $remove);
				$result += [
					'added' => count($add),
					'removed' => count($remove),
				];
				break;
		}
		// If the list has had everything removed from it, then just clear the list if it hasn't been cleared above already
		$count = $type->count();
		if ($count === 0 && !$already_cleared) {
			$type->items_clear();
		}
		$response->json()->setData($result + [
			'status' => $status,
			'message' => $message,
			'count' => $count,
		]);
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Widget::themeVariables()
	 */
	public function themeVariables(): array {
		$type = $this->selection_type();
		return parent::themeVariables() + [
			'target' => $this->option('target', '.row'),
			'container' => $this->option('container', '.list'),
			'selection_type' => $type,
			'count' => $type->count(),
			'actions' => $this->optionArray('actions', []),
		];
	}

	/**
	 *
	 * @param Database_Query_Select $query
	 */
	protected function hook_query_list(Database_Query_Select $query): void {
		if ($query->has_what('selected')) {
			// TODO Why is this called twice while listing?
			return;
		}
		$TYPE_ID = $this->selection_type()->id();
		$alias = $query->alias();
		$id_column = $this->application->ormRegistry($query->orm_class())->idColumn();
		$table = $this->application->ormRegistry(__NAMESPACE__ . '\\Selection_Item')->table();
		$query->join("LEFT OUTER JOIN $table sel_item ON sel_item.type=$TYPE_ID AND sel_item.id=$alias.$id_column");
		$query->what('*selected', 'sel_item.add');
	}
}
