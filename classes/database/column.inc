<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/database/column.inc $
 * @author Kent M. Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 * @package zesk
 * @subpackage database
 */
class Database_Column extends Options {

	/**
	 *
	 * @var Database_Table
	 */
	protected $table;

	/**
	 * Column name
	 *
	 * @var string
	 */
	protected $name = null;

	/**
	 * @return Database_Table
	 */
	function table(Database_Table $set = null) {
		if ($set !== null) {
			$this->table = $set;
			return $this;
		}
		return $this->table;
	}

	function __construct(Database_Table $table, $name, $options = null) {
		$this->table = $table;
		if (is_string($options)) {
			// Handle backwards compatibility
			zesk::deprecated();
			$args = func_get_args();
			$sql_type = $options;
			$options = avalue($args, 2, array());
			$options = array(
				'sql_type' => $sql_type
			) + (array) $options;
		}
		parent::__construct($options);
		$this->name($name);
	}

	function is_text() {
		$db = $this->table->database();
		$data_type = $db->data_type();
		return $data_type->is_text($this->sql_type());
	}
	/**
	 * Get/set previous name for a column
	 * @param string $name
	 * @return Database_Column string
	 */
	function previous_name($name = null) {
		if ($name === null) {
			return $this->option('previous_name');
		}
		$this->set_option('previous_name', $name);
		return $this;
	}

	/**
	 * Get/set column name
	 * @param string $set
	 * @return Database_Column string
	 */
	function name($set = null) {
		if ($set !== null) {
			$this->name = $set;
			return $this;
		}
		return $this->name;
	}

	/**
	 * Detect differences between database columns
	 *
	 * @param Database $db
	 * @param Database_Column $that
	 * @return array
	 */
	public final function differences(Database $db, Database_Column $that) {
		$data_type = $db->data_type();
		$name = $this->name();
		$this_native_type = $this->option("sql_type", "this");
		$that_native_type = $that->option("sql_type", "that");
		$diffs = array();
		if (!$data_type->native_types_equal($this_native_type, $that_native_type)) {
			$diffs["type"] = array(
				$this_native_type,
				$that_native_type
			);
		}
		if ($this->binary() !== $that->binary()) {
			$diffs["binary"] = array(
				$this->binary(),
				$that->binary()
			);
		}
		$threquired = $this->required();
		$thatRequired = $that->required();
		if ($threquired !== $thatRequired) {
			$diffs["required"] = array(
				$this->required(),
				$that->required()
			);
		}
		$thisDefault = $data_type->native_type_default($this_native_type, $this->default_value());
		$thatDefault = $data_type->native_type_default($that_native_type, $that->default_value());
		if ($thisDefault !== $thatDefault) {
			$diffs['defaults'] = array(
				$thisDefault,
				$thatDefault
			);
		}
		if (($thisUn = $this->option_bool("unsigned")) !== ($thatUn = $that->option_bool("unsigned"))) {
			$diffs['unsigned'] = array(
				$thisUn,
				$thatUn
			);
		}
		if ($this->is_increment() !== $that->is_increment()) {
			$diffs['increment'] = array(
				$this->is_increment(),
				$that->is_increment()
			);
		}
		$this_extras = $db->column_attributes($this);
		$that_extras = $db->column_attributes($that);
		foreach ($this_extras as $extra => $default) {
			$this_value = $this->option($extra, $default);
			$that_value = $that->option($extra, avalue($that_extras, $extra, $default));
			if ($this_value !== $that_value) {
				$diffs[$extra] = array(
					$this_value,
					$that_value
				);
			}
		}
		return $diffs;
	}
	public final function is_similar(Database $db, Database_Column $that, $debug = false) {
		$diffs = $this->differences($db, $that);
		if (count($diffs) > 0 && $debug) {
			$name = $this->name();
			log::debug("Database_Column::is_similar($name): Incompatible: {dump}", array(
				"dump" => php::dump($diffs),
				"diffs" => $diffs
			));
		}
		return count($diffs) === 0;
	}
	public final function has_sql_type() {
		return $this->has_option("sql_type", true);
	}
	public final function sql_type($set = null) {
		if ($set !== null) {
			$this->set_option("sql_type", $set);
			return $this;
		}
		return $this->option("sql_type", false);
	}
	public final function has_default_value($checkEmpty = false) {
		if ($checkEmpty) {
			$default = $this->default_value();
			return !empty($default);
		}
		return array_key_exists('default', $this->options);
	}
	public final function default_value($set = null) {
		if ($set === null) {
			return avalue($this->options, 'default');
		}
		$this->options['default'] = $set;
		return $this;
	}
	public final function binary($set = null) {
		if ($set !== null) {
			$this->binary = to_bool($set);
			return $this;
		}
		return $this->option_bool("binary");
	}
	public final function primary_key($set = null) {
		if (is_bool($set)) {
			$this->set_option('primary_key', $set);
			return $this;
		}
		return $this->option_bool("primary_key");
	}
	public final function increment($set = null) {
		if (is_bool($set)) {
			$this->set_option('serial', $set);
			return $this;
		}
		return to_bool($this->first_option("serial;increment"));
	}
	public final function is_increment() {
		return to_bool($this->first_option("increment;serial"));
	}
	public final function index_add($name, $type = Database_Index::Index) {
		if ($type == Database_Index::Index) {
			$opt = "index";
		} else if ($type == Database_Index::Unique) {
			$opt = "unique";
		} else if ($type == Database_Index::Primary) {
			$this->set_option("primary_key", true);
			$this->set_option("required", true);
			return true;
		} else {
			throw new Exception_Unimplemented("Database_Column::index_add($name, $type): Invalid type");
		}
		$indexes = $this->option_list($opt, array());
		if (in_array($name, $indexes))
			return true;
		$indexes[] = $name;
		$this->set_option($opt, $indexes);
		return true;
	}
	public final function indexes_types() {
		$indexNames = $this->option_list("Index", array());
		$uniqueNames = $this->option_list("Unique", array());
		$isPrimary = $this->option_bool("primary_key", false);
		$result = array();
		foreach ($uniqueNames as $name) {
			if (empty($name)) {
				$name = $this->name() . "_Unique";
			}
			$result[$name] = Database_Index::Unique;
		}
		foreach ($indexNames as $name) {
			if (empty($name)) {
				$name = $this->name() . "_Index";
			}
			$result[$name] = Database_Index::Index;
		}
		if ($isPrimary) {
			$result[""] = Database_Index::Primary;
		}
		return $result;
	}
	public final function not_null($set = null) {
		if ($set !== null) {
			$this->set_option('not null', true);
			return $this;
		}
		return $this->option_bool('not null', $this->option_bool('required', $this->primary_key()));
	}
	public final function required() {
		return $this->option_bool("required", $this->option_bool("not null", $this->primary_key()));
	}
	public final function is_index($type = "") {
		switch ($type) {
			case Database_Index::Index:
				return $this->has_option("Index", true);
			case Database_Index::Unique:
				return $this->has_option("Unique", true);
			case Database_Index::Primary:
				return $this->primary_key();
			default:
				return $this->has_option("Unique", true) || $this->has_option("Index", true) || $this->primary_key();
		}
	}
	function has_extras() {
		return $this->has_option("column_extras", true);
	}
	function extras($set = null) {
		if ($set === null) {
			return $this->option("column_extras");
		} else {
			$this->set_option("column_extras", $set);
		}
	}

	public function _debug_dump() {
		$vars = get_object_vars($this);
		$vars['table'] = $this->table->name();
		return "Object:" . __CLASS__ . " (\n" . text::indent(_dump($vars, true)) . "\n)";
	}

	public function __toString() {
		return $this->name;
	}
}

