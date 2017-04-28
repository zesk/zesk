<?php
	
$result = require_once dirname(__FILE__) . '/autoload.php';

/**
 * @deprecated 2017-03
 */
zesk()->deprecated("zesk.inc is deprecated, include autoload.php instead");

return $result;