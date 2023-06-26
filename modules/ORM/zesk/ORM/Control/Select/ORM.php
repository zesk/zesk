<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage widgets
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 *            Created on Tue Jul 15 16:22:33 EDT 2008
 */

namespace zesk\ORM;

/**
 *
 * @author kent
 *
 */
class Control_Select_ORM extends Control_Select {
	/**
	 *
	 * @var ?Class_Base
	 */
	protected ?Class_Base $class_object = null;

	/**
	 * @return ORMBase
	 */
	protected function model(): ORMBase {
		$class = $this->class;
		if (empty($class)) {
			return parent::model();
		}
		return $this->application->ormFactory($this->class);
	}

	protected function initialize(): void {
		if (empty($this->class)) {
			// Do not use "class" option - is also attribute on HTML tags. Use object_class
			$this->class = $this->option('object_class');
		}
		$class = $this->class_object = $this->application->class_ormRegistry($this->class);
		if (!$this->hasOption('text_column')) {
			$this->setOption('text_column', $class->text_column);
		}
		parent::initialize();
	}

	protected function _where() {
		$where = $this->option('where', '');
		if (!is_array($where)) {
			return [];
		}
		if ($this->object) {
			$where = $this->object->applyMap($where);
		}
		return $where;
	}

	public function setValue(mixed $set): self {
		$this->object->set($this->column(), null);
		return $this;
	}

	protected function idColumn() {
		return $this->option('idcolumn', $this->class_object->id_column);
	}

	protected function text_columns() {
		$text_column = $this->option('text_column', $this->class_object->name_column);
		if (!$text_column) {
			$text_column = $this->class_object->name_column;
		}
		$text_column = toList($text_column);
		$text_column = array_merge($text_column, $this->optionArray('text_columns'));
		return $text_column;
	}

	protected function hook_options() {
		$db = $this->class_object->database();
		$query = $this->application->ormRegistry($this->class)->querySelect();
		$prefix = $query->alias() . '.';

		$text_column = $this->text_columns();
		$what = ArrayTools::prefixValues(ArrayTools::valuesFlipCopy($text_column), $prefix);
		$query->addWhat('id', $prefix . $this->class_object->id_column);
		$query->addWhatIterable($what);
		$query->setOrderBy($this->option('order_by', $text_column));
		$query->appendWhere($this->_where());

		if (!$this->hasOption('format')) {
			$this->setOption('format', implode(' ', ArrayTools::wrapValues(array_keys($what), '{', '}')));
		}
		$this->callHook('options_query', $query);
		return $this->callHook('options_query_format', $query);
	}

	protected function hook_options_queryFormat(Database_Query_Select $query) {
		$format = $this->option('format');
		$rows = $query->toArray('id');
		foreach ($rows as $key => $row) {
			$rows[$key] = map($format, $row);
		}
		if ($this->optionBool('translate_after')) {
			$rows = $this->application->locale->__($rows);
		}
		return $rows;
	}

	public function where($where = null, $append = false) {
		if ($where !== null) {
			if ($append) {
				if (is_array($where)) {
					$where = [
						$where,
					];
				}
				$where = $this->optionArray('where', []) + $where;
			}
			$this->setOption('where', $where);
			return $this;
		}
		return $this->option('where');
	}
}
