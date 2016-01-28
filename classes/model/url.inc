<?php
class Model_URL extends Model {

	public $url = null;

	function __construct($url, $options = null) {
		$this->url = $url;
		parent::__construct(null, $options);
	}
}
