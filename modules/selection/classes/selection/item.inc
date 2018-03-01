<?php
namespace zesk;

/**
 *
 * @see Class_Selection_Item
 * @author kent
 *
 */
class Selection_Item extends ORM {
	/**
	 * Value in "add" for items which should be removed from the query
	 *
	 * @var integer
	 */
	const type_remove = 0;
	/**
	 * Value in "add" for items which should be added to the query
	 *
	 * @var integer
	 */
	const type_add = 1;
	
	/**
	 *
	 * @param unknown $class
	 */
	public function item($class) {
		try {
			return $this->orm_factory($class, $this->id)->fetch();
		} catch (Exception_ORM_NotFound $e) {
			return null;
		}
	}
	
	/**
	 *
	 * @param unknown $old_type
	 * @param unknown $new_type
	 * @param array $mapping
	 */
	public static function copy_duplicate(Application $application, $old_type, $new_type, array $mapping) {
		/* @var $class Class_ORM */
		$members = $application->class_orm_registry(__CLASS__)->column_types;
		unset($members['type']);
		$what = ArrayTools::flip_copy(array_keys($members));
		$what['*type'] = $new_type;
		$object = $application->orm_registry(__CLASS__);
		$object->query_insert_select()
			->what($what)
			->where('type', $old_type)
			->execute();
		foreach ($mapping as $old_query_id => $new_query_id) {
			$object->query_update()
				->value('query', $new_query_id)
				->where(array(
				'query' => $old_query_id,
				'type' => $new_type
			))
				->execute();
		}
	}
	
	/**
	 * Fix schema so UNIQUE keys work. MySQL does not silently ignore UNIQUE alters after version 5 at some point.
	 *
	 * @param ORM_Schema $schema
	 * @param Database_Table $table
	 * @param array $changes
	 * @return array
	 */
	protected function hook_schema_update_alter(ORM_Schema $schema, Database_Table $table, array $changes) {
		$before_changes = array();
		foreach ($changes as $change) {
			if (strpos($change, 'tid') !== false && strpos($change, 'UNIQUE') !== false) {
				$before_changes[] = "CREATE TABLE Selection_Item_UQ LIKE Selection_Item";
				$before_changes[] = "ALTER TABLE Selection_Item_UQ DROP INDEX tid";
				$before_changes[] = "ALTER TABLE Selection_Item_UQ ADD UNIQUE tid (`type`,`id`)";
				$before_changes[] = "REPLACE INTO Selection_Item_UQ SELECT * FROM Selection_Item";
				$before_changes[] = "DROP TABLE Selection_Item";
				$before_changes[] = "RENAME TABLE Selection_Item_UQ TO Selection_Item";
				break;
			}
		}
		return array_merge($before_changes, $changes);
	}
}
