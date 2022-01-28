<?php declare(strict_types=1);
/**
 * @copyright &copy; 2022 Market Acumen, Inc.
 */
namespace zesk;

/**
 * @author kent
 *
 */
class Model_List extends Model {
	public $offset = 0;

	public $limit = -1;

	public $total = -1;

	public $filter = null;

	/**
	 * @var Model_List
	 */
	public $pager = null;
}
