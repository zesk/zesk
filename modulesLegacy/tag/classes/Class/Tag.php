<?php declare(strict_types=1);
/**
 * @package zesk-modules
 * @subpackage tag
 * @author kent
 * @copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk\Tag;

use zesk\ORM\Class_Base;
use zesk\Exception_Semantics;
use zesk\PHP;

/**
 * @see zesk\Tag
 * @see Tag_Label
 * @author kent
 *
 */
abstract class Class_Tag extends Class_Base {
	/**
	 *
	 * @var string
	 */
	public $tag_column = 'tag_label';

	/**
	 * Linked column in OUR class which connects to foreign primary key
	 *
	 * @var string
	 */
	public $foreign_column = null;

	/**
	 * PHP Class name of ORM subclass
	 *
	 * @var string
	 */
	public $foreign_orm_class_name = null;

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Class_Base::initialize()
	 */
	public function initialize(): void {
		if (!$this->foreign_column) {
			throw new Exception_Semantics('{class} is misconfigured and needs foreign_column set', [
				'class' => get_class($this),
			]);
		}
		if (!$this->table) {
			$this->table = $this->option('tag_table_prefix', 'Tag_') . PHP::parseClass($this->foreign_orm);
		}
		$this->find_keys = [
			$this->tag_column,
			$this->foreign_column,
		];
		$this->column_types[$this->tag_column] = self::type_object;
		$this->column_types[$this->foreign_column] = self::type_object;

		$this->has_one[$this->tag_column] = Label::class;

		if ($this->foreign_orm_class_name) {
			$this->has_one[$this->foreign_column] = $this->foreign_orm_class_name;
		}
	}
}
