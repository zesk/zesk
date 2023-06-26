<?php declare(strict_types=1);
namespace zesk\ORM\Test;

use zesk\ORM\ORMBase;
use zesk\PHPUnit\TestCase;

/**
 *
 * @author kent
 *
 */
class TestDBQueryObject extends ORMBase {
	public function validate(TestCase $test): void {
		$test->assertTrue(!$this->memberIsEmpty('id'));
		$test->assertTrue(!$this->memberIsEmpty('foo'));
	}
}
