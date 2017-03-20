<?php
/**
 * @copyright &copy; 2016 Market Acumen, Inc.
 */
namespace zesk;

class Model_URL extends Model {

	public $url = null;

	function __construct($url, $options = null) {
		$this->url = $url;
		parent::__construct(null, $options);
	}
}
