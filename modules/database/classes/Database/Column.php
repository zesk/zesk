<?php
/**
 * @author Kent M. Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 * @package zesk
 * @subpackage database
 */
namespace zesk;

/**
 *
 * @author kent
 *
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
     *
     * @param Database_Table $table
     * @param unknown $name
     * @param unknown $options
     */
    public function __construct(Database_Table $table, $name, array $options = array()) {
        parent::__construct($options);
        $this->table = $table;
        $this->name($name);
        if ($this->has_option("sql_type")) {
            $this->sql_type($this->option("sql_type"));
        }
    }

    /**
     * @return Database_Table
     */
    public function table(Database_Table $set = null) {
        if ($set !== null) {
            $this->table = $set;
            return $this;
        }
        return $this->table;
    }

    public function size($set = null) {
        return $set === null ? $this->option_integer("size") : $this->set_option("size", $set);
    }

    public function is_text() {
        $db = $this->table->database();
        $data_type = $db->data_type();
        return $data_type->is_text($this->sql_type());
    }

    /**
     * Get/set previous name for a column
     * @param string $name
     * @return Database_Column string
     */
    public function previous_name($name = null) {
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
    public function name($set = null) {
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
    public function differences(Database $db, Database_Column $that) {
        $data_type = $db->data_type();
        $name = $this->name();
        $this_native_type = $this->option("sql_type", "this");
        $that_native_type = $that->option("sql_type", "that");
        $diffs = array();
        if (!$data_type->native_types_equal($this_native_type, $that_native_type)) {
            $diffs["type"] = array(
                $this_native_type,
                $that_native_type,
            );
        }
        if ($this->binary() !== $that->binary()) {
            $diffs["binary"] = array(
                $this->binary(),
                $that->binary(),
            );
        }
        $threquired = $this->required();
        $thatRequired = $that->required();
        if ($threquired !== $thatRequired) {
            $diffs["required"] = array(
                $this->required(),
                $that->required(),
            );
        }
        $thisDefault = $data_type->native_type_default($this_native_type, $this->default_value());
        $thatDefault = $data_type->native_type_default($that_native_type, $that->default_value());
        if ($thisDefault !== $thatDefault) {
            $diffs['defaults'] = array(
                $thisDefault,
                $thatDefault,
            );
        }
        if (($thisUn = $this->option_bool("unsigned")) !== ($thatUn = $that->option_bool("unsigned"))) {
            $diffs['unsigned'] = array(
                $thisUn,
                $thatUn,
            );
        }
        if ($this->is_increment() !== $that->is_increment()) {
            $diffs['increment'] = array(
                $this->is_increment(),
                $that->is_increment(),
            );
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
    public function attributes_differences(Database $db, Database_Column $that, $filter = null) {
        $this_extras = $db->column_attributes($this);
        $that_extras = $db->column_attributes($that);
        $diffs = array();
        if ($filter) {
            $this_extras = ArrayTools::filter($this_extras, $filter);
        }
        foreach ($this_extras as $extra => $default) {
            $this_value = $this->option($extra, $default);
            $that_value = $that->option($extra, avalue($that_extras, $extra, $default));
            if ($this_value !== $that_value) {
                $diffs[$extra] = array(
                    $this_value,
                    $that_value,
                );
            }
        }
        return $diffs;
    }

    final public function is_similar(Database $db, Database_Column $that, $debug = false) {
        $diffs = $this->differences($db, $that);
        if (count($diffs) > 0 && $debug) {
            $name = $this->name();
            $this->table->application->logger->debug("Database_Column::is_similar($name): Incompatible: {dump}", array(
                "dump" => PHP::dump($diffs),
                "diffs" => $diffs,
            ));
        }
        return count($diffs) === 0;
    }

    final public function has_sql_type() {
        return $this->has_option("sql_type", true);
    }

    final public function sql_type($set = null) {
        if ($set !== null) {
            $this->set_option("sql_type", strtolower($set));
            return $this;
        }
        return $this->option("sql_type", false);
    }

    final public function has_default_value($checkEmpty = false) {
        if ($checkEmpty) {
            $default = $this->default_value();
            return !empty($default);
        }
        return array_key_exists('default', $this->options);
    }

    final public function default_value($set = null) {
        if ($set === null) {
            return avalue($this->options, 'default');
        }
        $this->options['default'] = $set;
        return $this;
    }

    final public function binary($set = null) {
        if ($set !== null) {
            $this->binary = to_bool($set);
            return $this;
        }
        return $this->option_bool("binary");
    }

    final public function primary_key($set = null) {
        if (is_bool($set)) {
            $this->set_option('primary_key', $set);
            return $this;
        }
        return $this->option_bool("primary_key");
    }

    final public function increment($set = null) {
        if (is_bool($set)) {
            $this->set_option('serial', $set);
            return $this;
        }
        return to_bool($this->first_option("serial;increment"));
    }

    final public function is_increment() {
        return to_bool($this->first_option("increment;serial"));
    }

    final public function index_add($name, $type = Database_Index::Index) {
        if ($type == Database_Index::Index) {
            $opt = "index";
        } elseif ($type == Database_Index::Unique) {
            $opt = "unique";
        } elseif ($type == Database_Index::Primary) {
            $this->set_option("primary_key", true);
            $this->set_option("required", true);
            return true;
        } else {
            throw new Exception_Unimplemented("Database_Column::index_add($name, $type): Invalid type");
        }
        $indexes = $this->option_list($opt, array());
        if (in_array($name, $indexes)) {
            return true;
        }
        $indexes[] = $name;
        $this->set_option($opt, $indexes);
        return true;
    }

    final public function indexes_types() {
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

    final public function not_null($set = null) {
        if ($set !== null) {
            $this->set_option('not null', true);
            return $this;
        }
        return $this->option_bool('not null', $this->option_bool('required', $this->primary_key()));
    }

    final public function required() {
        return $this->option_bool("required", $this->option_bool("not null", $this->primary_key()));
    }

    final public function is_index($type = "") {
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

    public function has_extras() {
        return $this->has_option("column_extras", true);
    }

    public function extras($set = null) {
        if ($set === null) {
            return $this->option("column_extras");
        } else {
            $this->set_option("column_extras", $set);
        }
    }

    public function _debug_dump() {
        $vars = get_object_vars($this);
        $vars['table'] = $this->table->name();
        return "Object:" . __CLASS__ . " (\n" . Text::indent(_dump($vars, true)) . "\n)";
    }

    public function __toString() {
        return $this->name;
    }
}
