<?php
declare(strict_types=1);
/**
 * @author Kent M. Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 * @package zesk
 * @subpackage database
 */

namespace zesk;

use JetBrains\PhpStorm\Pure;

/**
 *
 * @author kent
 *
 */
class Database_Column extends Options {
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
	 * @var Database_Table
	 */
	protected Database_Table $table;

	/**
	 * Column name
	 *
	 * @var string
	 */
	protected string $name;

	/**
	 * @param Database_Table $table
	 * @param string $name
	 * @param array $options
	 */
	public function __construct(Database_Table $table, string $name, array $options = []) {
		parent::__construct($options);
		$this->table = $table;
		$this->setName($name);
		$this->setPrimaryKey($this->primaryKey());
	}

	/**
	 * @return Database_Table
	 */
	public function table(Database_Table $set = null): Database_Table {
		if ($set !== null) {
			$this->setTable($set);
			$this->table->application->deprecated('SetTable');
			$this->table = $set;
		}
		return $this->table;
	}

	/**
	 * @param Database_Table $set
	 * @return $this
	 */
	public function setTable(Database_Table $set): self {
		$this->table = $set;
		return $this;
	}

	/**
	 * Get the size of the column
	 *
	 * @return int
	 */
	public function size($set = null): int {
		if ($set !== null) {
			$this->table->application->deprecated('setter size');
		}
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
		$data_type = $db->data_type();
		return $data_type->is_text($this->sqlType());
	}

	/**
	 * Get previous name for a column
	 * @return string
	 */
	#[Pure]
	public function previousName(): string {
		return $this->option(self::OPTION_PREVIOUS_NAME, '');
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
	 * Get/set column name
	 * @param string $set
	 * @return Database_Column string
	 */
	public function name(string $set = null): string {
		if ($set !== null) {
			$this->table->application->deprecated('setter');
			$this->setName($set);
		}
		return $this->name;
	}

	/**
	 * Get/set column name
	 * @param string $set
	 * @return Database_Column string
	 */
	public function setName(string $set): self {
		$this->name = $set;
		return $this;
	}

	/**
	 * Detect differences between database columns
	 *
	 * @param Database $db
	 * @param Database_Column $that
	 * @return array
	 */
	public function differences(Database $db, Database_Column $that): array {
		$data_type = $db->data_type();
		$name = $this->name();
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
		$thisDefault = $data_type->native_type_default($this_native_type, $this->default_value());
		$thatDefault = $data_type->native_type_default($that_native_type, $that->default_value());
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
		if ($this->is_increment() !== $that->is_increment()) {
			$diffs[self::OPTION_INCREMENT] = [
				$this->is_increment(),
				$that->is_increment(),
			];
		}
		$result = $db->column_differences($this, $that, $diffs);
		if (is_array($result)) {
			$diffs = $result + $diffs;
		}
		return $diffs;
	}

	/**
	 *
	 * @param Database $db
	 * @param Database_Column $that
	 * @return array
	 */
	public function attributes_differences(Database $db, Database_Column $that, $filter = null): array {
		$this_extras = $db->column_attributes($this);
		$that_extras = $db->column_attributes($that);
		$diffs = [];
		if ($filter) {
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
	 * @param Database $db
	 * @param Database_Column $that
	 * @param bool $debug
	 * @return bool
	 */
	final public function isSimilar(Database $db, Database_Column $that, bool $debug = false): bool {
		$diffs = $this->differences($db, $that);
		if (count($diffs) > 0 && $debug) {
			$name = $this->name();
			$this->table->application->logger->debug("Database_Column::is_similar($name): Incompatible: {dump}", [
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
	#[Pure]
	final public function hasSQLType(): bool {
		return $this->hasOption(self::OPTION_SQL_TYPE, true);
	}

	/**
	 * @return string
	 */
	#[Pure]
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
	#[Pure]
	final public function hasDefaultValue(bool $checkEmpty = false): bool {
		return $this->hasOption(self::OPTION_DEFAULT, $checkEmpty);
	}

	/**
	 *
	 * @return mixed
	 */
	#[Pure]
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
	 * @param $set
	 * @return bool
	 */
	final public function binary($set = null): bool {
		if ($set !== null) {
			$this->table->application->deprecated('setter');
			$this->setBinary(to_bool($set));
		}
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
	#[Pure]
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
	 * @param $set
	 * @return bool
	 */
	final public function increment($set = null): bool {
		if (is_bool($set)) {
			$this->table->application->deprecated('increment setter');
			$this->setIncrement($set);
		}
		return $this->isIncrement();
	}

	/**
	 * @return bool
	 */
	#[Pure]
	final public function isIncrement(): bool {
		return to_bool($this->firstOption([self::OPTION_SERIAL, self::OPTION_INCREMENT]));
	}

	/**
	 * @param $name
	 * @param $type
	 * @return bool
	 * @throws Exception_Unimplemented
	 */
	final public function addIndex($name, string $type = Database_Index::TYPE_INDEX): self {
		if ($type == Database_Index::TYPE_INDEX) {
			$opt = 'index';
		} elseif ($type == Database_Index::TYPE_UNIQUE) {
			$opt = 'unique';
		} elseif ($type == Database_Index::TYPE_PRIMARY) {
			$this->setOption(self::OPTION_PRIMARY_KEY, true);
			$this->setOption(self::OPTION_REQUIRED, true);
			return $this;
		} else {
			throw new Exception_Unimplemented("Database_Column::index_add($name, $type): Invalid type");
		}
		$indexes = $this->optionIterable($opt, []);
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
		$indexNames = $this->optionArray(self::OPTION_INDEX, []);
		$uniqueNames = $this->optionArray(self::OPTION_UNIQUE, []);
		$isPrimary = $this->primaryKey();
		$result = [];
		foreach ($uniqueNames as $name) {
			if (empty($name)) {
				$name = $this->name() . '_Unique';
			}
			$result[$name] = Database_Index::TYPE_UNIQUE;
		}
		foreach ($indexNames as $name) {
			if (empty($name)) {
				$name = $this->name() . '_Index';
			}
			$result[$name] = Database_Index::TYPE_INDEX;
		}
		if ($isPrimary) {
			$result[Database_Index::NAME_PRIMARY] = Database_Index::TYPE_PRIMARY;
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
	#[Pure]
	final public function inUniqueIndex(): bool {
		return $this->hasOption(self::OPTION_UNIQUE, true);
	}

	/**
	 * @return bool
	 */
	#[Pure]
	final public function inIndex(): bool {
		return $this->hasOption(self::OPTION_INDEX, true);
	}

	/**
	 * @param string $of_type
	 * @return bool
	 */
	#[Pure]
	final public function isIndex(string $of_type = ''): bool {
		switch ($of_type) {
			case Database_Index::TYPE_INDEX:
				return $this->inIndex();
			case Database_Index::TYPE_UNIQUE:
				return $this->inUniqueIndex();
			case Database_Index::TYPE_PRIMARY:
				return $this->primaryKey();
			default:
				return $this->inIndex() || $this->inUniqueIndex() || $this->primaryKey();
		}
	}

	/**
	 * @return bool
	 */
	#[Pure]
	public function hasExtras(): bool {
		return $this->hasOption(self::OPTION_COLUMN_EXTRAS, true);
	}

	/**
	 * @param $set
	 * @return mixed|void
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
		return 'Object:' . __CLASS__ . " (\n" . Text::indent(_dump($vars, true)) . "\n)";
	}

	/**
	 * @return string
	 */
	public function __toString(): string {
		return $this->name;
	}

	/*---------------------------------------------------------------------------------------------------------*\
	  ---------------------------------------------------------------------------------------------------------
	  ---------------------------------------------------------------------------------------------------------
			 _                               _           _
		  __| | ___ _ __  _ __ ___  ___ __ _| |_ ___  __| |
		 / _` |/ _ \ '_ \| '__/ _ \/ __/ _` | __/ _ \/ _` |
		| (_| |  __/ |_) | | |  __/ (_| (_| | ||  __/ (_| |
		 \__,_|\___| .__/|_|  \___|\___\__,_|\__\___|\__,_|
				   |_|
	  ---------------------------------------------------------------------------------------------------------
	  ---------------------------------------------------------------------------------------------------------
	\*---------------------------------------------------------------------------------------------------------*/

	/**
	 * @param string $set
	 * @return string
	 * @deprecated 2022-05
	 */
	final public function sql_type($set = null): string {
		if ($set !== null) {
			$this->table->application->deprecated('setter');
			$this->setSQLType(strval($set));
		}
		return $this->sqlType();
	}

	/**
	 * @param $set deprecated
	 * @return bool
	 * @deprecated 2022-05
	 */
	final public function primary_key(bool $set = null): bool {
		if (is_bool($set)) {
			$this->table->application->deprecated('setter');
			$this->setPrimaryKey($set);
		}
		return $this->primaryKey();
	}

	/**
	 * @return bool
	 * @deprecated 2022-05
	 */
	public function is_text(): bool {
		return $this->isText();
	}

	/**
	 * @param Database $db
	 * @param Database_Column $that
	 * @param $debug
	 * @return bool
	 * @deprecated 2022-05
	 */
	final public function is_similar(Database $db, Database_Column $that, $debug = false): bool {
		return $this->isSimilar($db, $that, $debug);
	}

	/**
	 * @param bool $checkEmpty
	 * @return bool
	 * @deprecated 2022-05
	 */
	final public function has_default_value(bool $checkEmpty = false) {
		return $this->hasDefaultValue($checkEmpty);
	}

	/**
	 * @param $set
	 * @return $this|mixed|null
	 * @deprecated 2022-05
	 */
	final public function default_value($set = null): mixed {
		if ($set === null) {
			return $this->options['default'] ?? null;
		}
		$this->options['default'] = $set;
		return $this;
	}

	/**
	 * Get previous name for a column
	 * @return string
	 * @deprecated 2022-05
	 */
	public function previous_name(): string {
		return $this->previousName();
	}

	/**
	 * @param $name
	 * @param $type
	 * @return self
	 * @throws Exception_Unimplemented
	 * @deprecated 2022-05
	 */
	final public function index_add($name, $type = Database_Index::TYPE_INDEX) {
		return $this->addIndex($name, $type);
	}

	/**
	 * @return bool
	 * @deprecated 2022-05
	 */
	public function has_extras(): bool {
		return $this->hasExtras();
	}

	/**
	 * @return bool
	 * @deprecated 2022-05
	 */
	final public function is_increment(): bool {
		$this->table->application->deprecated('old style');
		return $this->isIncrement();
	}

	/**
	 * @return array
	 * @deprecated 2022-05
	 */
	final public function indexes_types() {
		return $this->indexesTypes();
	}

	/**
	 * @param $set
	 * @return $this|bool
	 * @deprecated 2022-05
	 */
	final public function not_null($set = null) {
		$this->table->application->deprecated('not_null');
		if (is_bool($set)) {
			$this->setNotNull($set);
		}
		return $this->notNull();
	}

	/**
	 * @param $type
	 * @return bool
	 * @deprecated 2022-05
	 */
	final public function is_index($type = '') {
		return $this->isIndex($type);
	}
}
