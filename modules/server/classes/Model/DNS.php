<?php
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

    public function store() {
        $this->valid = true;
    }
}
