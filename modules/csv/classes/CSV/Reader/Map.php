<?php
/**
 * @package zesk
 * @subpackage tools
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 */
namespace zesk;

/**
 * CSV_Reader_Map
 *
 * Read from a CSV file and convert rows into objects easily
 *
 * @todo Refactor so this works on any array input, not just CSVs
 *
 * @package zesk
 * @subpackage tools
 */
class CSV_Reader_Map extends CSV_Reader {
    /**
     * A read map group allows you to read a subset of a row and map the column names into new names. This is useful when
     * mapping a CSV row into one or more zesk\ORM subclasses.
     *
     * @var array[readmapname]
     */
    protected $ReadMapGroup;

    /**
     * The read map group defaults provides default values, per read map, used to populate the result when the value does not exist.
     *
     * @var array[readmapname]
     */
    protected $ReadMapGroupDefault;

    /**
     * The read map group types provides column types, per read map, used to
     * @todo Move ReadMap functionality to another class, doesn't belong with RAW self functionality
     *
     * @var array[readmapname]
     */
    protected $ReadMapGroupTypes;
    
    /**
     *
     * @var array
     */
    protected $translation_map = array();
    
    /**
     *
     * @param unknown $filename
     * @param unknown $options
     */
    public function __construct($filename = null, array $options = array()) {
        parent::__construct($filename, $options);
        $this->ReadMapGroup = array();
        $this->ReadMapGroupDefault = array();
        $this->ReadMapGroupTypes = array();
    }
    
    /**
     * Create
     *
     * @param string $filename
     * @param string $options
     * @return self
     */
    public static function factory($filename, array $options = array()) {
        return new self($filename, $options);
    }
    
    /**
     * Set or get a simple mapping for reading objects from a CSV, for example
     *
     * Usage:
     *
     * 		`$reader->read_map("foo", array("namen" => "name"))` - Sets the read map "foo" to array
     * 		`$reader->read_map("foo")` - Returns the current row formatted using read map "foo"
     * 		`$reader->read_map()` - Returns all read map names associated with this reader (["foo","bar"])
     *
     * @param string $name Name of this map - case insensitive
     * @param array $map Array of csv_column => new_key - if null then returns named map
     * @param string $mapTypes
     * @param string $defaultMap
     * @throws Exception_Semantics
     * @throws Exception_Key
     * @return boolean
     */
    public function read_map($name = null, array $map = null, array $mapTypes = null, array $defaultMap = null) {
        if (!is_array($this->Headers)) {
            throw new Exception_Semantics("Must have headers before setting map");
        }
        if ($name === null) {
            return array_keys($this->ReadMapGroup);
        }
        if ($map === null) {
            return $this->_get_read_map($name);
        }
        $this->headers();
        $mapGroup = array();
        foreach ($map as $column => $objectMember) {
            $column = strtolower($column);
            if (!isset($this->HeadersToIndex[$column])) {
                throw new Exception_Key("CSV::readSetMap($name,...): $column not found in headers {headers_to_index}", array(
                    "headers_to_index" => $this->HeadersToIndex,
                ));
            } else {
                $indexes = $this->HeadersToIndex[$column];
                if (is_array($indexes)) {
                    foreach ($indexes as $index) {
                        $mapGroup[$index] = $objectMember;
                    }
                } else {
                    $mapGroup[$indexes] = $objectMember;
                }
            }
        }
        $name = strtolower($name);
        $this->ReadMapGroup[$name] = $mapGroup;
        if (is_array($defaultMap)) {
            $this->ReadMapGroupDefault[$name] = $defaultMap;
        }
        if (is_array($mapTypes)) {
            $this->ReadMapGroupTypes[$name] = $mapTypes;
        }
        return true;
    }
    
    /**
     * Given a source row $data, and a type map
     * @param array $row
     * @param array $typeMap
     * @param array $defaultMap
     * @throws Exception_Key
     * @return array
     */
    private static function apply_types(array $row, array $typeMap, array $defaultMap) {
        foreach ($typeMap as $k => $type) {
            $v = avalue($row, $k);
            if ($v !== null) {
                if (is_string($type)) {
                    switch ($type) {
                        case "boolean":
                            $row[$k] = to_bool($v);

                            break;
                        case "timestamp":
                        case "datetime":
                            $row[$k] = Timestamp::factory($v);

                            break;
                        default:
                            throw new Exception_Key("Unknown type map $type in CSV::apply_types");
                    }
                } elseif (is_array($type)) {
                    $v = avalue($type, $v, avalue($defaultMap, $k));
                    if ($v !== null) {
                        $row[$k] = $v;
                    }
                }
            }
        }
        return $row;
    }
    
    /**
     * Retrieve the read map of name
     *
     * @param string $name
     * @throws Exception_Key
     * @return array
     */
    private function _get_read_map($name) {
        $lowname = strtolower($name);
        if (!isset($this->ReadMapGroup[$lowname])) {
            throw new Exception_Key("CSV::readMap($name) doesn't exist");
        }
        $g = $this->ReadMapGroup[$lowname];
        $result = array();
        $r = $this->Row;
        foreach ($g as $index => $cols) {
            $value = $r[$index];
            if (!is_array($cols)) {
                $cols = array(
                    $cols,
                );
            }
            foreach ($cols as $col) {
                if (isset($result[$col])) {
                    $rcol = &$result[$col];
                    if (is_array($rcol)) {
                        $rcol[] = $value;
                    } else {
                        $rcol = array(
                            $rcol,
                            $value,
                        );
                    }
                } else {
                    $result[$col] = $value;
                }
            }
        }
        if (isset($this->ReadMapGroupDefault[$lowname])) {
            foreach ($this->ReadMapGroupDefault[$lowname] as $k => $v) {
                if (array_key_exists($k, $result)) {
                    continue;
                }
                $result[$k] = $v;
            }
        }
        $t = avalue($this->ReadMapGroupTypes, $lowname);
        if ($t) {
            $result = self::apply_types($result, $t, avalue($this->ReadMapGroupDefault, $lowname, array()));
        }
        return $result;
    }
    
    /**
     * Set columns to be converted to boolean upon reading
     *
     * @param mixed $columns
     * @return self
     */
    public function add_translation_map_boolean($columns) {
        return $this->_add_translation_map_type(to_list($columns), Class_ORM::type_boolean);
    }
    
    /**
     * Add translation map for a specific type
     *
     * @param array $columns
     * @param string $type Class_ORM::type_foo type
     * @return self
     */
    private function _add_translation_map_type(array $columns, $type) {
        foreach ($columns as $column) {
            if (!in_array($column, $this->Headers)) {
                throw new Exception_Key("Unknown header key {key} in CSV reader for file {file} (type {type})", array(
                    "key" => $column,
                    "type" => $type,
                    "file" => $this->FileName,
                ));
            }
            $this->translation_map[$column] = $type;
        }
        return $this;
    }
    
    /**
     *
     * @param array $row
     */
    protected function postprocess_row(array $row) {
        foreach ($this->translation_map as $column => $type) {
            if (!array_key_exists($column, $row)) {
                continue;
            }
            $method = "_convert_to_$type";
            $row[$column] = $this->$method($row[$column], $row, $column);
        }
        return $row;
    }
    
    /**
     * Convert data to boolean
     */
    private function _convert_to_boolean($value, array $row, $column) {
        return to_bool($value);
    }
}
