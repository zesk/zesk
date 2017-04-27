<?php
	
$result = require_once dirname(__FILE__) . '/autoload.php';

/**
 * @deprecated 2017-03
 */
zesk()->deprecated("zesk.inc is deprecated, include zesk.php instead");

return $result;