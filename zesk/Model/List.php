<?php declare(strict_types=1);
/**
 * @copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk;

/**
 * @author kent
 *
 */
class Model_List extends Model {
	protected string $class_name = '';

	public int $offset = 0;

	public int $limit = -1;

	public int $total = -1;

	public array $filter = [];

	public $pager = null;
}
