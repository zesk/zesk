<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Model_DNS extends Model {
	public $valid = false;

	public $old = null;

	public $new = null;

	public $lookup = null;

	public function store(): void {
		$this->valid = true;
	}
}
