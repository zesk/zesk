<?php declare(strict_types=1);
/**
 * @copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Controller_Selection_Item extends Controller_ORM {
	protected $class = 'zesk\\Selection_Item';

	/**
	 *
	 * @var Selection_Type
	 */
	private $type = null;

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Controller_ORM::widget_control_classes()
	 */
	protected function widget_control_classes($action) {
		if ($action === 'list') {
			$type_class = strtolower($this->type->class);
			$widget_control_classes = $this->optionArray('widget_control_classes');
			if (array_key_exists($type_class, $widget_control_classes)) {
				return $widget_control_classes[$type_class];
			}
		}
		return parent::widget_control_classes($action);
	}

	/**
	 *
	 * @param Selection_Type $type
	 */
	protected function action_list(Selection_Type $type) {
		if (!$this->user->can('list_items', $type)) {
			$this->json([
				'status' => true,
				'message' => $this->application->locale->__('No permission to edit selection type.'),
			]);
			return;
		}
		$this->type = $type;
		return $this->_action_default('list');
	}

	protected function action_delete_id(Selection_Type $type, $id): void {
		$this->type = $type;
		$class = $type->class;
		if (!$this->user->can('delete_items', $type)) {
			$this->json([
				'status' => true,
				'message' => $this->application->locale->__('No permission to delete selection items.'),
			]);
			return;
		}
		$this->application->ormRegistry($this->class)
			->query_update()
			->value('add', 0)
			->where([
			'id' => $id,
			'type' => $type,
		])
			->execute();

		if ($this->request->has('target')) {
			$target = $this->request->get('target');
			$this->response->jquery('$(' . JSON::encode($target) . ').slideUp(\'slow\', function() { $(this).keysRemove(); });');
		}
		$this->json([
			'status' => true,
			'message' => $this->application->locale->__('Deleted selection item.'),
			'content' => '',
		] + $this->response->to_json());
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Controller_Theme::control()
	 */
	protected function control(Control $control, Model $object = null, array $options = []) {
		if (method_exists($control, 'selection_type')) {
			$control->selection_type($this->type);
		}
		return parent::control($control, $object, $options);
	}
}
