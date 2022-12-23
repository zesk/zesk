<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage ORM
 * @author kent
 * @copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk\ORM;

use zesk\Database_Column;
use zesk\Exception_Semantics;
use zesk\Exception_Unimplemented;
use zesk\Exception_Deprecated;

/**
 *
 * @author kent
 *
 */
class Database_Adapter_MySQL extends Database_Adapter {
	/**
	 * @param Database_Column $column
	 * @return void
	 * @throws Exception_Semantics
	 * @throws Exception_Unimplemented
	 * @throws Exception_Deprecated
	 */
	public function database_column_set_type(Database_Column $column): void {
		$type_name = $column->option('type', false);
		$is_bin = $column->optionBool('binary');
		$size = $column->optionInt('size');
		if (!$type_name) {
			throw new Exception_Semantics(__CLASS__ . '::type_set_sql_type(...): "Type" is not set! ' . print_r($column, true));
		}
		switch (strtolower($type_name)) {
			case Class_Base::TYPE_ID:
				$column->setOption('primary_key', true);
				$column->setOption('sql_type', 'integer');
				$column->increment(true);
				$column->setOption('unsigned', true);
				return ;
			case Class_Base::TYPE_OBJECT:
				$column->setOption('sql_type', 'integer');
				$column->setOption('unsigned', true);
				return ;
			case Class_Base::TYPE_INTEGER:
				$column->setOption('sql_type', 'integer');
				return ;
			case Class_Base::type_character:
				$size = !is_numeric($size) ? 1 : $size;
				$column->setOption('sql_type', "char($size)");
				return ;
			case Class_Base::type_text:
				$column->setOption('sql_type', 'text');
				return;
			case 'varchar':
			case Class_Base::TYPE_STRING:
				if (!is_numeric($size)) {
					$column->setOption('sql_type', $is_bin ? 'blob' : 'text');
				} else {
					$column->setOption('sql_type', $is_bin ? "varbinary($size)" : "varchar($size)");
				}
				return ;
			case Class_Base::type_boolean:
				$column->setOption('sql_type', 'bit(1)');
				return ;
			case 'varbinary':
			case Class_Base::TYPE_SERIALIZE:
			case Class_Base::type_binary:
			case Class_Base::type_hex:
			case Class_Base::type_hex32:
				if (!is_numeric($size)) {
					$column->setOption('sql_type', 'blob');
				} else {
					$column->setOption('sql_type', "varbinary($size)");
				}
				$column->binary(true);
				return ;
			case Class_Base::type_byte:
				$column->setOption('sql_type', 'tinyint(1)');
				$column->setOption('Unsigned', true);
				return ;
			case Class_Base::type_decimal:
				$intP = $column->optionInt('integer_precision', 10);
				$decP = $column->optionInt('decimal_precision', 2);
				$width = $intP + $decP;
				$column->setOption('sql_type', "decimal($width,$decP)");
				return ;
			case Class_Base::type_real:
				$column->setOption('sql_type', 'real');
				return ;
			case Class_Base::TYPE_FLOAT:
				$column->setOption('sql_type', 'double');
				return ;
			case Class_Base::type_date:
				$column->setOption('sql_type', 'date');
				return ;
			case Class_Base::type_time:
				$column->setOption('sql_type', 'time');
				return ;
			case Class_Base::type_datetime:
			case Class_Base::TYPE_MODIFIED:
			case Class_Base::type_created:
			case Class_Base::TYPE_TIMESTAMP:
				$column->setOption('sql_type', 'timestamp');
				return ;
			case 'checksum':
				zesk()->deprecated(); // ?? This used anywhere?
				$column->setOption('sql_type', 'char(32)');
				return ;
			case 'password':
				zesk()->deprecated(); // ?? This used anywhere?
				$column->setOption('sql_type', 'varchar(32)');
				return ;
		}

		throw new Exception_Unimplemented('{method}({type}) Not handled', [
			'method' => __METHOD__,
			'type' => $type_name,
		]);
	}
}
