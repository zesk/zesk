<?php
/**
 * 
 */
use zesk\Object;
use zesk\JSON;
use zesk\Model;

/**
 * 
 * @author kent
 *
 */
class Controller_Selection_Item extends Controller_Object {
	protected $class = "Selection_Item";

	/**
	 *
	 * @var Selection_Type
	 */
	private $type = null;
	protected function widget_control_classes($action) {
		if ($action === "list") {
			return array(
				'Control_List_Selection_Item_' . $this->type->class => "list"
			);
		}
		return parent::widget_control_classes($action);
	}
	protected function action_list(Selection_Type $type) {
		if (!$this->user->can("list_items", $type)) {
			$this->json(array(
				"status" => true,
				"message" => __('No permission to edit selection type.')
			));
			return;
		}
		$this->type = $type;
		$class = $type->class;
		return $this->_action_default("list");
	}
	protected function action_delete_id(Selection_Type $type, $id) {
		$this->type = $type;
		$class = $type->class;
		if (!$this->user->can("delete_items", $type)) {
			$this->json(array(
				"status" => true,
				"message" => __('No permission to delete selection items.')
			));
			return;
		}
		Object::class_query_update($this->class)->value('add', 0)->where(array(
			"id" => $id,
			'type' => $type
		))->execute();

		if ($this->request->has('target')) {
			$target = $this->request->get('target');
			$this->response->jquery("\$(" . JSON::encode($target) . ").slideUp('slow', function() { $(this).remove(); });");
		}
		$this->json(array(
			"status" => true,
			"message" => __('Deleted selection item.'),
			"content" => ""
		) + $this->response->to_json());
	}

	protected function control(Control $control, Model $object = null, array $options = array()) {
		if (method_exists($control, "selection_type")) {
			$control->selection_type($this->type);
		}
		return parent::control($control, $object, $options);
	}
}
