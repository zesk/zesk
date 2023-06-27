<?php
declare(strict_types=1);
/**
 *
 */
namespace zesk\MySQL;

use zesk\PHPUnit\TestCase;

/**
 *
 * @author kent
 *
 */
class SQLParserTest extends TestCase
{
	protected array $load_modules = [
		'MySQL',
	];

	public static function data_provider_split_order_by(): array
	{
		return [
			// Suffix, string
			[
				'((DAYOFYEAR(X.birthday)-DAYOFYEAR(CURDATE())+365)%365) ASC, SOME_FUNCTION(CURDATE(),\'2014-12-30\') DESC',
				[
					'((DAYOFYEAR(X.birthday)-DAYOFYEAR(CURDATE())+365)%365) ASC',
					'SOME_FUNCTION(CURDATE(),\'2014-12-30\') DESC',
				],
				'((DAYOFYEAR(X.birthday)-DAYOFYEAR(CURDATE())+365)%365) DESC, SOME_FUNCTION(CURDATE(),\'2014-12-30\') ASC',
			],
			// Suffix, array
			[
				[
					'((DAYOFYEAR(X.birthday)-DAYOFYEAR(CURDATE())+365)%365) ASC',
					'SOME_FUNCTION(CURDATE(),\'2014-12-30\') DESC',
				],
				[
					'((DAYOFYEAR(X.birthday)-DAYOFYEAR(CURDATE())+365)%365) ASC',
					'SOME_FUNCTION(CURDATE(),\'2014-12-30\') DESC',
				],
				[
					'((DAYOFYEAR(X.birthday)-DAYOFYEAR(CURDATE())+365)%365) DESC',
					'SOME_FUNCTION(CURDATE(),\'2014-12-30\') ASC',
				],
			],
			// No suffix, string
			[
				'((DAYOFYEAR(X.birthday)-DAYOFYEAR(CURDATE())+365)%365), SOME_FUNCTION(CURDATE(),\'2014-12-30\')',
				[
					'((DAYOFYEAR(X.birthday)-DAYOFYEAR(CURDATE())+365)%365)',
					'SOME_FUNCTION(CURDATE(),\'2014-12-30\')',
				],
				'((DAYOFYEAR(X.birthday)-DAYOFYEAR(CURDATE())+365)%365) DESC, SOME_FUNCTION(CURDATE(),\'2014-12-30\') DESC',
			],
			// No suffix, array
			[
				[
					'((DAYOFYEAR(X.birthday)-DAYOFYEAR(CURDATE())+365)%365)',
					'SOME_FUNCTION(CURDATE(),\'2014-12-30\')',
				],
				[
					'((DAYOFYEAR(X.birthday)-DAYOFYEAR(CURDATE())+365)%365)',
					'SOME_FUNCTION(CURDATE(),\'2014-12-30\')',
				],
				[
					'((DAYOFYEAR(X.birthday)-DAYOFYEAR(CURDATE())+365)%365) DESC',
					'SOME_FUNCTION(CURDATE(),\'2014-12-30\') DESC',
				],
			],
			// Random tests
			[
				'IF(A=B,0,1),ColumnName,\'some string\',IF(CURDATE()<modified,\'2014-12-01\',modified)',
				[
					'IF(A=B,0,1)',
					'ColumnName',
					'\'some string\'',
					'IF(CURDATE()<modified,\'2014-12-01\',modified)',
				],
				'IF(A=B,0,1) DESC, ColumnName DESC, \'some string\' DESC, IF(CURDATE()<modified,\'2014-12-01\',modified) DESC',
			],
		];
	}

	/**
	 * @dataProvider data_provider_split_order_by
	 */
	public function test_split_order_by($order_by, $expected_split, $expected_reverse): void
	{
		$parser = $this->application->databaseRegistry()->sqlParser();

		if (is_array($order_by)) {
			$actual = [];
			foreach ($order_by as $k => $v) {
				$result = $parser->splitOrderBy($v);
				if (count($result) === 1) {
					$result = array_pop($result);
				}
				$actual[$k] = $result;
			}
		} else {
			$actual = $parser->splitOrderBy($order_by);
		}

		$this->assertEquals($expected_split, $actual);

		$actual_reverse = $parser->reverseOrderBy($order_by);

		// 		$this->log('reverseOrderBy');
		// 		$this->log($actual_reverse);
		// 		$this->log($expected_reverse);
		$this->assertEquals($actual_reverse, $expected_reverse);
	}
}
