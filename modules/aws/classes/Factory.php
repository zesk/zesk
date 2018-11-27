<?php

/**
 * @version $URL: https://code.marketacumen.com/zesk/trunk/modules/aws/classes/aws/factory.inc $
 * @package ruler
 * @subpackage aws
 * @author $Author: kent $
 * @copyright Copyright &copy; 2011, Market Ruler, LLC
 */
namespace zesk\AWS;

class Factory {
	/*
	 * See lib/aws/config-sample.inc.php for explanations
	 */
	public static function globals() {
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
			'AWS_ENABLE_EXTENSIONS' => 'false',
		);
	}

	public static function defines() {
		/*
		 * Must define the following globals prior to including the SDK class below
		 * @todo 2017 - is this still required by AWS library?
		 */
		foreach (Factory::globals() as $define => $default) {
			if (!defined($define)) {
				define($define, $default);
			}
		}
	}
}
