<?php
/**
 * @version $URL: https://code.marketacumen.com/zesk/trunk/modules/aws/classes/aws/factory.inc $
 * @package ruler
 * @subpackage aws
 * @author $Author: kent $
 * @copyright Copyright &copy; 2011, Market Ruler, LLC
 */
class AWS_Factory {
	/*
	 * See lib/aws/config-sample.inc.php for explanations
	 */
	static function globals() {
		return array(
			'AWS_KEY' => '',
			'AWS_SECRET_KEY' => '',
			'AWS_ACCOUNT_ID' => '',
			'AWS_CANONICAL_ID' => '',
			'AWS_CANONICAL_NAME' => '',
			'AWS_CERTIFICATE_AUTHORITY' => false,
			'AWS_DEFAULT_CACHE_CONFIG' => '',
			'AWS_MFA_SERIAL' => '',
			'AWS_CLOUDFRONT_KEYPAIR_ID' => '',
			'AWS_CLOUDFRONT_PRIVATE_KEY_PEM' => '',
			'AWS_ENABLE_EXTENSIONS' => 'false'
		);
	}
}

/*
 * Must define the following globals prior to including the SDK class below
 */
foreach (AWS_Factory::globals() as $define => $default) {
	if (!defined($define)) {
		define($define, $default);
	}
}
