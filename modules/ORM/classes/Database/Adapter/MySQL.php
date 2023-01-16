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
		$type_name = $column->optionString('type');
		$is_bin = $column->optionBool('binary');
		$size = $column->optionInt('size');
		if (!$type_name) {
			throw new Exception_Semantics(__CLASS__ . '::type_set_sql_type(...): "Type" is not set! ' . print_r($column, true));
		}
		switch (strtolower($type_name)) {
			case Class_Base::TYPE_ID:
				$column->setPrimaryKey(true);
				$column->setSQLType('integer');
				$column->setIncrement(true);
				$column->setOption('unsigned', true);
				return ;
			case Class_Base::TYPE_OBJECT:
				$column->setSQLType('integer');
				$column->setOption('unsigned', true);
				return ;
			case Class_Base::TYPE_INTEGER:
				$column->setSQLType('integer');
				return ;
			case Class_Base::TYPE_CHARACTER:
				$size = !is_numeric($size) ? 1 : $size;
				$column->setSQLType("char($size)");
				return ;
			case Class_Base::TYPE_TEXT:
				$column->setSQLType('text');
				return;
			case 'varchar':
			case Class_Base::TYPE_STRING:
				if (!is_numeric($size)) {
					$column->setSQLType($is_bin ? 'blob' : 'text');
				} else {
					$column->setSQLType($is_bin ? "varbinary($size)" : "varchar($size)");
				}
				return ;
			case Class_Base::TYPE_BOOL:
				$column->setSQLType('bit(1)');
				return ;
			case 'varbinary':
			case Class_Base::TYPE_SERIALIZE:
			case Class_Base::TYPE_BINARY:
			case Class_Base::TYPE_HEX:
			case Class_Base::TYPE_HEX32:
				if (!is_numeric($size)) {
					$column->setSQLType('blob');
				} else {
					$column->setSQLType("varbinary($size)");
				}
				$column->binary(true);
				return ;
			case Class_Base::TYPE_BYTE:
				$column->setSQLType('tinyint(1)');
				$column->setOption('Unsigned', true);
				return ;
			case Class_Base::TYPE_DECIMAL:
				$intP = $column->optionInt('integer_precision', 10);
				$decP = $column->optionInt('decimal_precision', 2);
				$width = $intP + $decP;
				$column->setSQLType("decimal($width,$decP)");
				return ;
			case Class_Base::TYPE_REAL:
				$column->setSQLType('real');
				return ;
			case Class_Base::TYPE_DOUBLE:
				$column->setSQLType('double');
				return ;
			case Class_Base::TYPE_DATE:
				$column->setSQLType('date');
				return ;
			case Class_Base::TYPE_TIME:
				$column->setSQLType('time');
				return ;
			case Class_Base::TYPE_DATETIME:
			case Class_Base::TYPE_MODIFIED:
			case Class_Base::TYPE_CREATED:
			case Class_Base::TYPE_TIMESTAMP:
				$column->setSQLType('timestamp');
				return ;
			case 'checksum':
				zesk()->deprecated(); // ?? This used anywhere?
				$column->setSQLType('char(32)');
				return ;
			case 'password':
				zesk()->deprecated(); // ?? This used anywhere?
				$column->setSQLType('varchar(32)');
				return ;
		}

		throw new Exception_Unimplemented('{method}({type}) Not handled', [
			'method' => __METHOD__,
			'type' => $type_name,
		]);
	}
}
