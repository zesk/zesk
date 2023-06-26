<?php
declare(strict_types=1);
/**
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 * @package zesk
 * @subpackage database
 */

namespace zesk\Database;

use zesk\ArrayTools;
use zesk\Deprecated;
use zesk\Exception\Unimplemented;
use zesk\Options;
use zesk\PHP;
use zesk\Text;

/**
 *
 * @author kent
 *
 */
class Column extends Options {
	public const OPTION_REQUIRED = 'required';

	public const OPTION_DEFAULT = 'default';

	public const OPTION_INDEX = 'index';

	public const OPTION_UNIQUE = 'unique';

	public const OPTION_SQL_TYPE = 'sql_type';

	public const OPTION_NOT_NULL = 'not null';

	public const OPTION_UNSIGNED = 'unsigned';

	public const OPTION_SIZE = 'size';

	public const OPTION_SERIAL = 'serial';

	public const OPTION_INCREMENT = 'increment';

	public const OPTION_COLUMN_EXTRAS = 'column_extras';

	public const OPTION_BINARY = 'binary';

	public const OPTION_PRIMARY_KEY = 'primary_key';

	public const OPTION_PREVIOUS_NAME = 'previous_name';

	/**
	 *
	 * @var Table
	 */
	protected Table $table;

	/**
	 * Column name
	 *
	 * @var string
	 */
	protected string $name;

	/**
	 * @param Table $table
	 * @param string $name
	 * @param array $options
	 */
	public function __construct(Table $table, string $name, array $options = []) {
		parent::__construct($options);
		$this->table = $table;
		$this->setName($name);
		$this->setPrimaryKey($this->primaryKey());
	}

	/**
	 * @return Table
	 */
	public function table(): Table {
		return $this->table;
	}

	/**
	 * @param Table $set
	 * @return $this
	 */
	public function setTable(Table $set): self {
		$this->table = $set;
		return $this;
	}

	/**
	 * Get the size of the column
	 *
	 * @return int
	 */
	public function size(): int {
		return $this->optionInt(self::OPTION_SIZE);
	}

	/**
	 * @param int $set
	 * @return void
	 */
	public function setSize(int $set): void {
		$this->setOption(self::OPTION_SIZE, $set);
	}

	/**
	 * @return bool
	 */
	public function isText(): bool {
		$db = $this->table->database();
		$data_type = $db->types();
		return $data_type->is_text($this->sqlType());
	}

	/**
	 * Get previous name for a column
	 * @return string
	 */
	public function previousName(): string {
		return strval($this->option(self::OPTION_PREVIOUS_NAME, ''));
	}

	/**
	 * Get/set previous name for a column
	 * @param string $name
	 * @return self
	 */
	public function setPreviousName(string $name): self {
		$this->setOption(self::OPTION_PREVIOUS_NAME, $name);
		return $this;
	}

	/**
	 * Get column name
	 * @return Column string
	 */
	public function name(): string {
		return $this->name;
	}

	/**
	 * Set column name
	 * @param string $set
	 * @return Column string
	 */
	public function setName(string $set): self {
		$this->name = $set;
		return $this;
	}

	/**
	 * Detect differences between database columns
	 *
	 * @param Column $that
	 * @return array
	 */
	public function differences(Column $that): array {
		$db = $this->table()->database();
		$data_type = $db->types();
		$this_name = $this->name();
		$that_name = $that->name();
		if ($this_name !== $that_name) {
			$diffs['name'] = [
				$this_name,
				$that_name,
			];
		}
		$this_native_type = $this->option(self::OPTION_SQL_TYPE, 'this');
		$that_native_type = $that->option(self::OPTION_SQL_TYPE, 'that');
		$diffs = [];
		if (!$data_type->native_types_equal($this_native_type, $that_native_type)) {
			$diffs['type'] = [
				$this_native_type,
				$that_native_type,
			];
		}
		if ($this->binary() !== $that->binary()) {
			$diffs[self::OPTION_BINARY] = [
				$this->binary(),
				$that->binary(),
			];
		}
		$this_required = $this->required();
		$that_required = $that->required();
		if ($this_required !== $that_required) {
			$diffs[self::OPTION_REQUIRED] = [
				$this->required(),
				$that->required(),
			];
		}
		$thisDefault = $data_type->native_type_default($this_native_type, $this->defaultValue());
		$thatDefault = $data_type->native_type_default($that_native_type, $that->defaultValue());
		if ($thisDefault !== $thatDefault) {
			$diffs[self::OPTION_DEFAULT] = [
				$thisDefault,
				$thatDefault,
			];
		}
		if (($thisUn = $this->optionBool(self::OPTION_UNSIGNED)) !== ($thatUn = $that->optionBool(self::OPTION_UNSIGNED))) {
			$diffs[self::OPTION_UNSIGNED] = [
				$thisUn,
				$thatUn,
			];
		}
		if ($this->isIncrement() !== $that->isIncrement()) {
			$diffs[self::OPTION_INCREMENT] = [
				$this->isIncrement(),
				$that->isIncrement(),
			];
		}
		return $db->columnDifferences($this, $that) + $diffs;
	}

	/**
	 *
	 * @param Base $db
	 * @param Column $that
	 * @param null|string|array $filter Only show differences between selected attributes
	 * @return array
	 */
	public function attributes_differences(Base $db, Column $that, string|array $filter = null): array {
		$this_extras = $db->columnAttributes($this);
		$that_extras = $db->columnAttributes($that);
		$diffs = [];
		if ($filter !== null) {
			$this_extras = ArrayTools::filter($this_extras, $filter);
		}
		foreach ($this_extras as $extra => $default) {
			$this_value = $this->option($extra, $default);
			$that_value = $that->option($extra, $that_extras[$extra] ?? $default);
			if ($this_value !== $that_value) {
				$diffs[$extra] = [
					$this_value,
					$that_value,
				];
			}
		}
		return $diffs;
	}

	/**
	 * @param Column $that
	 * @param bool $debug
	 * @return bool
	 */
	final public function isSimilar(Column $that, bool $debug = false): bool {
		$diffs = $this->differences($that);
		if (count($diffs) > 0 && $debug) {
			$name = $this->name();
			$this->table->application->logger->debug("Column::isSimilar($name): Incompatible: {dump}", [
				'dump' => PHP::dump($diffs),
				'diffs' => $diffs,
			]);
		}
		return count($diffs) === 0;
	}

	/**
	 * This column has an associated SQL type for the database
	 *
	 * @return bool
	 */
	final public function hasSQLType(): bool {
		return $this->hasOption(self::OPTION_SQL_TYPE, true);
	}

	/**
	 * @return string
	 */
	final public function sqlType(): string {
		return $this->option(self::OPTION_SQL_TYPE, '');
	}

	/**
	 * Set the type used in the database
	 *
	 * @param string $set
	 * @return $this
	 */
	final public function setSQLType(string $set): self {
		$this->setOption(self::OPTION_SQL_TYPE, strtolower($set));
		return $this;
	}

	/**
	 * Does this have a default value?
	 *
	 * @param bool $checkEmpty
	 * @return bool
	 */
	final public function hasDefaultValue(bool $checkEmpty = false): bool {
		return $this->hasOption(self::OPTION_DEFAULT, $checkEmpty);
	}

	/**
	 *
	 * @return mixed
	 */
	final public function defaultValue(): mixed {
		return $this->option(self::OPTION_DEFAULT);
	}

	/**
	 * @param mixed $set
	 * @return $this
	 */
	final public function setDefaultValue(mixed $set): self {
		return $set === null ? $this->clearOption(self::OPTION_DEFAULT) : $this->setOption(self::OPTION_DEFAULT, $set);
	}

	/**
	 * Get binary flag
	 *
	 * @return bool
	 */
	final public function binary(): bool {
		return $this->optionBool(self::OPTION_BINARY);
	}

	/**
	 * Set binary flag
	 *
	 * @param bool $set
	 * @return $this
	 */
	final public function setBinary(bool $set): self {
		return $this->setOption(self::OPTION_BINARY, $set);
	}

	/**
	 * @return bool
	 */
	final public function primaryKey(): bool {
		return $this->optionBool(self::OPTION_PRIMARY_KEY);
	}

	/**
	 * @param bool $set
	 * @return $this
	 */
	final public function setPrimaryKey(bool $set): self {
		$this->setOption(self::OPTION_PRIMARY_KEY, $set);
		return $this;
	}

	/**
	 * @param bool $set
	 * @return $this
	 */
	final public function setIncrement(bool $set): self {
		$this->setOption(self::OPTION_SERIAL, $set);
		$this->clearOption(self::OPTION_INCREMENT);
		return $this;
	}

	/**
	 * @return bool
	 */
	final public function increment(): bool {
		return $this->isIncrement();
	}

	/**
	 * @return bool
	 */
	final public function isIncrement(): bool {
		return toBool($this->firstOption([self::OPTION_SERIAL, self::OPTION_INCREMENT]));
	}

	/**
	 * @param string $name
	 * @param string $type
	 * @return $this
	 * @throws Unimplemented
	 */
	final public function addIndex(string $name, string $type = Index::TYPE_INDEX): self {
		if ($type == Index::TYPE_INDEX) {
			$opt = 'index';
		} elseif ($type == Index::TYPE_UNIQUE) {
			$opt = 'unique';
		} elseif ($type == Index::TYPE_PRIMARY) {
			$this->setOption(self::OPTION_PRIMARY_KEY, true);
			$this->setOption(self::OPTION_REQUIRED, true);
			return $this;
		} else {
			throw new Unimplemented("Column::index_add($name, $type): Invalid type");
		}
		$indexes = $this->optionIterable($opt);
		if (in_array($name, $indexes)) {
			return $this;
		}
		$indexes[] = $name;
		$this->setOption($opt, $indexes);
		return $this;
	}

	/**
	 * @return array
	 */
	final public function indexesTypes(): array {
		$indexNames = $this->optionArray(self::OPTION_INDEX);
		$uniqueNames = $this->optionArray(self::OPTION_UNIQUE);
		$isPrimary = $this->primaryKey();
		$result = [];
		foreach ($uniqueNames as $name) {
			if (empty($name)) {
				$name = $this->name() . '_Unique';
			}
			$result[$name] = Index::TYPE_UNIQUE;
		}
		foreach ($indexNames as $name) {
			if (empty($name)) {
				$name = $this->name() . '_Index';
			}
			$result[$name] = Index::TYPE_INDEX;
		}
		if ($isPrimary) {
			$result[Index::NAME_PRIMARY] = Index::TYPE_PRIMARY;
		}
		return $result;
	}

	/**
	 * @param bool $set
	 * @return self
	 */
	final public function setNotNull(bool $set): self {
		return $this->setOption(self::OPTION_NOT_NULL, $set);
	}

	/**
	 * @param bool $set
	 * @return self
	 */
	final public function setNull(bool $set): self {
		return $this->setOption(self::OPTION_NOT_NULL, !$set);
	}

	/**
	 * Inversion of NOT NULL
	 *
	 * @return bool
	 */
	final public function null(): bool {
		return !$this->optionBool(self::OPTION_NOT_NULL);
	}

	final public function notNull(): bool {
		return $this->optionBool(self::OPTION_NOT_NULL);
	}

	/**
	 * @return bool
	 */
	final public function required(): bool {
		return $this->optionBool(self::OPTION_REQUIRED, $this->optionBool(self::OPTION_NOT_NULL, $this->primaryKey()));
	}

	/**
	 * @return bool
	 */
	final public function inUniqueIndex(): bool {
		return $this->hasOption(self::OPTION_UNIQUE, true);
	}

	/**
	 * @return bool
	 */
	final public function inIndex(): bool {
		return $this->hasOption(self::OPTION_INDEX, true);
	}

	/**
	 * @param string $of_type
	 * @return bool
	 */
	final public function isIndex(string $of_type = ''): bool {
		return match ($of_type) {
			Index::TYPE_INDEX => $this->inIndex(),
			Index::TYPE_UNIQUE => $this->inUniqueIndex(),
			Index::TYPE_PRIMARY => $this->primaryKey(),
			default => $this->inIndex() || $this->inUniqueIndex() || $this->primaryKey(),
		};
	}

	/**
	 * @return bool
	 */
	public function hasExtras(): bool {
		return $this->hasOption(self::OPTION_COLUMN_EXTRAS, true);
	}

	/**
	 * @param $set
	 * @return mixed|void
	 * @throws Deprecated
	 */
	public function extras($set = null) {
		if ($set !== null) {
			$this->table->application->deprecated('extras setter');
			$this->setOption(self::OPTION_COLUMN_EXTRAS, $set);
		}
		return $this->option(self::OPTION_COLUMN_EXTRAS);
	}

	/**
	 * @param string $set
	 * @return self
	 */
	public function setExtras(string $set): self {
		return $this->setOption(self::OPTION_COLUMN_EXTRAS, $set);
	}

	/**
	 * @return string
	 */
	public function _debug_dump(): string {
		$vars = get_object_vars($this);
		$vars['table'] = $this->table->name();
		return 'Object:' . __CLASS__ . " (\n" . Text::indent(_dump($vars)) . "\n)";
	}

	/**
	 * @return string
	 */
	public function __toString(): string {
		return $this->name;
	}
}
