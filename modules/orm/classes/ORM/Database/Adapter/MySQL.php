<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage ORM
 * @author kent
 * @copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class ORM_Database_Adapter_MySQL extends ORM_Database_Adapter {
	public function database_column_set_type(Database_Column $column) {
		$type_name = $column->option('type', false);
		$is_bin = $column->optionBool('binary');
		$size = $column->optionInt('size');
		if (!$type_name) {
			throw new Exception_Semantics(__CLASS__ . '::type_set_sql_type(...): "Type" is not set! ' . print_r($column, true));
		}
		switch (strtolower($type_name)) {
			case Class_ORM::type_id:
				$column->setOption('primary_key', true);
				$column->setOption('sql_type', 'integer');
				$column->increment(true);
				$column->setOption('unsigned', true);
				return true;
			case Class_ORM::type_object:
				$column->setOption('sql_type', 'integer');
				$column->setOption('unsigned', true);
				return true;
			case Class_ORM::type_integer:
				$column->setOption('sql_type', 'integer');
				return true;
			case Class_ORM::type_character:
				$size = !is_numeric($size) ? 1 : $size;
				$column->setOption('sql_type', "char($size)");
				return true;
			case Class_ORM::type_text:
				$column->setOption('sql_type', 'text');
				return true;
			case 'varchar':
				zesk()->deprecated();
			// fall through
			// no break
			case Class_ORM::type_string:
				if (!is_numeric($size)) {
					$column->setOption('sql_type', $is_bin ? 'blob' : 'text');
				} else {
					$column->setOption('sql_type', $is_bin ? "varbinary($size)" : "varchar($size)");
				}
				return true;
			case Class_ORM::type_boolean:
				$column->setOption('sql_type', 'bit(1)');
				return true;
			case 'varbinary':
			case Class_ORM::type_serialize:
			case Class_ORM::type_binary:
			case Class_ORM::type_hex:
			case Class_ORM::type_hex32:
				if (!is_numeric($size)) {
					$column->setOption('sql_type', 'blob');
				} else {
					$column->setOption('sql_type', "varbinary($size)");
				}
				$column->binary(true);
				return true;
			case Class_ORM::type_byte:
				$column->setOption('sql_type', 'tinyint(1)');
				$column->setOption('Unsigned', true);
				return true;
			case Class_ORM::type_decimal:
				$intP = $column->firstOption('integer_precision', 10);
				$decP = $column->firstOption('decimal_precision', 2);
				$width = $intP + $decP;
				$column->setOption('sql_type', "decimal($width,$decP)");
				return true;
			case Class_ORM::type_real:
				$column->setOption('sql_type', 'real');
				return true;
			case Class_ORM::type_double:
				$column->setOption('sql_type', 'double');
				return true;
			case Class_ORM::type_date:
				$column->setOption('sql_type', 'date');
				return true;
			case Class_ORM::type_time:
				$column->setOption('sql_type', 'time');
				return true;
			case Class_ORM::type_datetime:
			case Class_ORM::type_modified:
			case Class_ORM::type_created:
			case Class_ORM::type_timestamp:
				$column->setOption('sql_type', 'timestamp');
				return true;
			case 'checksum':
				zesk()->deprecated(); // ?? This used anywhere?
				$column->setOption('sql_type', 'char(32)');
				return true;
			case 'password':
				zesk()->deprecated(); // ?? This used anywhere?
				$column->setOption('sql_type', 'varchar(32)');
				return true;
		}

		throw new Exception_Unimplemented('{method}({type}) Not handled', [
			'method' => __METHOD__,
			'type' => $type_name,
		]);
	}
}
