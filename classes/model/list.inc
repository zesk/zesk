<?php
/**
 * @todo move to zesk\ namespace
 * @author kent
 *
 */
class Model_List extends zesk\Model {

	public $offset = 0;

	public $limit = -1;

	public $total = -1;

	public $filter = null;

	/**
	 * @var Model_List
	 */
	public $pager = null;
}
