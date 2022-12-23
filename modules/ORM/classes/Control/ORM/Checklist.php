<?php declare(strict_types=1);
/**
 *
 */
namespace zesk\ORM;

/**
 *
 * @author kent
 *
 */
class Control_ORM_Checklist extends Control_Checklist {
	protected array $objects = [];

	/**
	 *
	 * {@inheritDoc}
	 * @see Widget::is_visible()
	 */
	public function is_visible() {
		return count($this->objects) !== 0;
	}

	/**
	 *
	 * @param array $where
	 */
	public function where(array $where = null) {
		return is_array($where) ? $this->setOption('where', $where) : $this->optionArray('where');
	}

	/**
	 *
	 * @return mixed[]|array[]
	 */
	protected function hook_options() {
		$object = $this->application->ormRegistry($this->class);
		$name_col = $object->nameColumn();
		$this->objects = [];
		$control_options = [];
		$query = $this->application->ormRegistry($this->class)->querySelect();
		$query->where($this->optionArray('where'));
		$query->order_by($this->option('order_by', $name_col));
		$this->callHook('options_query', $query);
		$iterator = $query->ormIterator();
		foreach ($iterator as $id => $object) {
			$this->objects[$id] = $object;
			$control_options[$id] = $this->object_format_option_label($object);
		}
		return $control_options;
	}

	protected function object_format_option_label(ORMBase $object) {
		return $object->member($object->nameColumn());
	}

	/**
	 * (non-PHPdoc)
	 * @see Control_Options::themeVariables()
	 */
	public function themeVariables(): array {
		return parent::themeVariables() + [
			'control_objects' => $this->objects,
		];
	}
}
