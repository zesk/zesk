<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/object/xml.inc $
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 * @package zesk
 * @subpackage system
 */
use zesk\Template;
use zesk\Database_Table;

/**
 * 
 * @author kent
 *
 */
class Object_XML {
	static function from_table_name($db_url, $table_name) {
		$x = app()->database_factory($db_url);
		$table = $x->databaseTable($table_name);
		$xml = new XML_Writer();
		self::from_database_table($xml, $table);
		return $xml->output(array(
			"xml_singles" => true,
			"xml_header" => true
		));
	}
	static function from_database_table(XML_Writer_Interface $XML_Writer, Database_Table $table) {
		/* @var $XML_Writer IXMLWriter */
		/* @var $table Database_Table */
		/* @var $database Database */
		$database = $table->database();
		$table_prefix = $database->table_prefix();
		$name = $table->name();
		if (begins($name, $table_prefix)) {
			$name = substr($name, strlen($table_prefix));
		}
		$XML_Writer->push("Object", array(
			"name" => $name
		));
		$table->toXML($XML_Writer);
		$cols = $table->columns();
		$XML_Writer->push("Widgets", array(
			"method" => "edit"
		));
		foreach ($cols as $col) {
			$size = null;
			$basic_type = $database->sqlBasicType($col->sqlType(), $size);
			/* @var $col Database_Column */
			$col->toXMLControl($XML_Writer, $basic_type, $size);
		}
		$XML_Writer->pop("Widgets");
		$XML_Writer->push("Widgets", array(
			"method" => "view"
		));
		foreach ($cols as $col) {
			$size = null;
			$basic_type = $database->sqlBasicType($col->sqlType(), $size);
			/* @var $col Database_Column */
			$col->toXMLView($XML_Writer, $basic_type, $size);
		}
		$XML_Writer->pop("Widgets");
		$XML_Writer->add("table", array(
			"dbtype" => $database->type()
		), str_replace($table, '{table}', $database->sql_get_create_table($table)));
		$XML_Writer->pop("Object");
	}
	static function to_php_class($content) {
		$xml = new SimpleXMLElement($content);
		$t = new Template("object/xml_object.tpl", array(
			"XML" => $xml
		));
		return $t->render();
	}
}
