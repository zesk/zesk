<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage tag
 * @author kent
 * @copyright &copy; 2022 Market Acumen, Inc.
 */
namespace zesk\Tag;

use zesk\Control;
use zesk\Selection_Type;
use zesk\Selection_Item;
use zesk\HTML;
use zesk\Exception_Semantics;

/**
 * @author kent
 */
class Control_Tags extends Control {
	/**
	 *
	 * @var Selection_Type
	 */
	protected $selection_type = null;

	/**
	 * When submitting, the value of what to change
	 *
	 * @var string
	 */
	protected $action_value = null;

	/**
	 *
	 * @var unknown
	 */
	private $_labels_generated = null;

	/**
	 * Debugging for SQL generated
	 *
	 * @var array
	 */
	private $debug_sqls = [];

	/**
	 * Valid action to prefix a label ID submitted to this widget, as a list separated by commas.
	 *
	 * So "-23,+43,-12" is a valid value assuming 23, 43, 12 are Labels which are valid
	 *
	 * @var array
	 */
	private static $actions = [
		'+' => '_action_add',
		'-' => '_action_remove',
	];

	/**
	 * Getter/setter for selection type
	 *
	 * @param Selection_Type $set
	 * @return \zesk\Selection_Type|\zesk\ORM\Control_Tags
	 */
	public function selection_type(Selection_Type $set = null) {
		if ($set === null) {
			return $this->selection_type;
		}
		$this->selection_type = $set;
		return $this;
	}

	/**
	 *
	 * @return array
	 */
	private function _parse_value($value) {
		$locale = $this->application->locale;
		$actions = explode(',', $value);
		$labels = $this->_labels();
		$result = [];
		foreach ($actions as $action) {
			if (empty($action)) {
				continue;
			}
			if (strlen($action) < 2) {
				$this->error($locale->__('Invalid value: {action}', [
					'action' => $action,
				]), $this->name());
				return null;
			}
			$action_prefix = $action[0];
			if (!array_key_exists($action_prefix, self::$actions)) {
				$this->error($locale->__('Invalid value not a valid prefix: {action}', [
					'action' => $action,
				]), $this->name());
				return null;
			}
			$label_id = intval(substr($action, 1));
			if (!array_key_exists($label_id, $labels)) {
				$this->error($locale->__('Invalid value not a valid label: {action}', [
					'action' => $action,
				]), $this->name());
				return null;
			}
			$result[$action_prefix][] = $labels[$label_id];
		}
		return $result;
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Widget::load()
	 */
	public function load() {
		$this->action_value = $this->request->get($this->action_form_element_name());
		return parent::load();
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Widget::validate()
	 */
	public function validate() {
		if (!parent::validate()) {
			return false;
		}
		return is_array($this->_parse_value($this->action_value));
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Widget::render()
	 */
	public function render() {
		$debug = [];
		return HTML::etag('pre', implode('<br />', $debug)) . parent::render();
	}

	/**
	 *
	 * @throws Exception_Semantics
	 * @return \zesk\Database_Query_Select
	 */
	protected function query_tags_used() {
		if (!$this->selection_type) {
			throw new Exception_Semantics('Need selection_type set');
		}
		if (!$this->orm_class_name()) {
			throw new Exception_Semantics('Need orm_class_name() set');
		}
		$class_orm = $this->class_orm();
		assert($class_orm instanceof Class_Tag);

		$member = $class_orm->foreign_column;

		$application = $this->application;
		$selection_item_table = $application->orm_registry(Selection_Item::class)->table();
		$tags_query = $application->orm_registry($this->orm_class_name())
			->query_select('main')
			->link(Label::class, [
			'alias' => 'label',
			'path' => 'tag_label',
		])
			->addWhat('id', 'label.id')
			->addWhat('*total', 'COUNT(DISTINCT items.id)')
			->join("INNER JOIN $selection_item_table items ON items.id=main.$member")
			->where([
			'items.type' => $this->selection_type->id(),
		])
			->group_by([
			'label.id',
		]);
		return $tags_query;
	}

	/**
	 * Convert a list of labels and remove offending labels using a hook
	 *
	 * @param Iterable $labels
	 * @return \zesk\Tag\Label[]
	 */
	public function filter_labels($labels) {
		$labels = $this->call_hook_arguments('filter_labels', [
			$labels,
		], $labels);
		$by_id = [];
		foreach ($labels as $label) {
			if ($label instanceof Label) {
				$by_id[$label->id()] = $label;
			}
		}
		uasort($by_id, fn (Label $a, Label $b) => strcasecmp($a->name, $b->name));
		return $by_id;
	}

	/**
	 *
	 * @return string
	 */
	private function action_form_element_name() {
		return $this->name() . '_action';
	}

	/**
	 *
	 */
	protected function _labels() {
		if ($this->_labels_generated) {
			return $this->_labels_generated;
		}
		return $this->_labels_generated = $this->filter_labels($this->application->tag_module()->list_labels());
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Widget::theme_variables()
	 */
	public function theme_variables() {
		$tags_query = $this->query_tags_used();

		$labels = $this->_labels();

		return parent::theme_variables() + [
			'action_form_element_name' => $this->action_form_element_name(),
			'selection_type' => $this->selection_type,
			'tags_query' => strval($tags_query),
			'labels_used' => $tags_query->to_array('id', 'total'),
			'labels' => $labels,
		];
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Widget::submit()
	 */
	public function submit() {
		if (!parent::submit()) {
			return false;
		}
		$this->debug_sqls = [];
		$actions = $this->_parse_value($this->action_value);
		foreach ($actions as $action => $labels) {
			$method = self::$actions[$action];
			foreach ($labels as $label) {
				if (!$this->$method($label)) {
					return false;
				}
			}
		}
		if ($this->application->development()) {
			$this->response()->response_data([
				'debug_sql' => $this->debug_sqls,
			], true);
		}
		return true;
	}

	/**
	 *
	 * @param array $labels
	 * @return boolean
	 */
	public function _action_add(Label $label) {
		$orm = $this->application->orm_registry($this->orm_class_name());
		/* @var $orm Tag */
		$type = $this->selection_type();

		$query = $orm->apply_label_selection($label, $type);
		$orm->control_add($this, $query);

		if (!$query->execute()) {
			$error_id = $this->name() . '-' . $label->id();
			$this->error($this->application->locale->__(__CLASS__ . ':=Unable to add label {name}', $label->variables()), $error_id);
			return false;
		}
		$this->debug_sqls[] = strval($query);
		return true;
	}

	/**
	 *
	 * @param Label $label
	 * @return boolean
	 */
	public function _action_remove(Label $label) {
		$orm = $this->application->orm_registry($this->orm_class_name());
		/* @var $orm Tag */
		$type = $this->selection_type();

		$query = $orm->remove_label_selection($label, $type);
		$orm->control_remove($this, $query);

		if (!$query->execute()) {
			$error_id = $this->name() . '-' . $label->id();
			$this->error($this->application->locale->__(__CLASS__ . ':=Unable to remove label {name}', $label->variables()), $error_id);
			return false;
		}
		$this->debug_sqls[] = strval($query);
		return true;
	}
}
