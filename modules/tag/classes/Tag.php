<?php declare(strict_types=1);
/**
 *
 */
namespace zesk\Tag;

use zesk\ORM;
use zesk\Application;
use zesk\Selection_Type;
use zesk\Database_Query_Insert_Select;
use zesk\Database_Query_Delete;

/**
 * @see Class_Tag
 * @see Module_Tag
 * @property Label $tag_label
 */
abstract class Tag extends ORM {
	/**
	 * @var $class Class_Tag
	 */
	public $class = null;

	/**
	 * @return string
	 */
	public function foreign_orm_class_name() {
		return $this->class->foreign_orm_class_name;
	}

	/**
	 * Name of column in this object which represents the foreign key to our tagged object table
	 *
	 * @return string
	 */
	public function foreign_column() {
		return $this->class->foreign_column;
	}

	/**
	 * Reassign to another tag
	 *
	 * @param Tag $old
	 * @param Tag $new
	 * @return integer
	 */
	public function reassign($old, $new) {
		return $this->query_update('X')
			->value('X.tag_label', $new)
			->addWhere('X.tag_label', $old)
			->execute()
			->affectedRows();
		;
	}

	/**
	 *
	 * @param Application $application
	 * @return \zesk\string[]
	 */
	public static function taggables(Application $application) {
		$subclasses = $application->classes->subclasses(self::class);
		$result = [];
		foreach ($subclasses as $subclass) {
			try {
				$instance = $application->ormRegistry($subclass);
			} catch (\Exception $e) {
				continue;
			}
			$result[$application->objects->resolve($instance->foreign_orm_class_name())] = $subclass;
		}
		return $result;
	}

	/**
	 *
	 * @param ORM $orm
	 */
	public static function taggable(ORM $orm) {
		$app = $orm->application;
		$class_name = $app->objects->resolve(get_class($orm));
		return self::taggables($app)[$class_name] ?? null;
	}

	/**
	 *
	 * @param Selection_Type $type
	 */
	public function apply_label_selection(Label $label, Selection_Type $type) {
		$selected = $type->items_selected();

		$selected_query = $selected->query();

		$selected_query->addWhatIterable([
			'*tag_label' => $label->id(),
			$this->foreign_column() => 'id',
		]);
		$query = Database_Query_Insert_Select::fromSelect($selected_query);
		$query->into($this->table());
		$query->replace(true);

		return $query;
	}

	/**
	 *
	 * @param Selection_Type $type
	 */
	public function remove_label_selection(Label $label, Selection_Type $type) {
		$selected = $type->items_selected();

		$selected_query = $selected->query();

		$selected_query->addWhatIterable([
			$this->foreign_column() => 'id',
		]);
		// @todo log issue against this and fix
		$query = $this->query_delete()->where([
			'tag_label' => $label,
			'*' . $this->foreign_column() . '| IN ' => '(' . strval($selected_query) . ')',
		]);
		return $query;
	}

	/**
	 * Add internal or extra fields to a query
	 *
	 * @param Database_Query_Insert_Select $query
	 * @return
	 */
	public function control_add(Control_Tags $control, Database_Query_Insert_Select $query) {
		return;
	}

	/**
	 * Add internal or extra fields to a query
	 *
	 * @param Database_Query_Insert_Select $query
	 * @return
	 */
	public function control_keysRemove(Control_Tags $control, Database_Query_Delete $query) {
		return;
	}
}
