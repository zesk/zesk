<?php
/**
 * Define globals before including AmazonEC2
 */
zesk::define_globals(Cloud_EC2::CLOUD_EC2_GLOBALS);

/**
 * 
 * @author kent
 *
 */
class Cloud_EC2 extends AmazonEC2 implements Cloud {

	const CLOUD_EC2_GLOBALS = array(
		'AWS_KEY' => '', 
		'AWS_SECRET_KEY' => '', 
		'AWS_ACCOUNT_ID' => '', 
		'AWS_CANONICAL_ID' => '', 
		'AWS_CANONICAL_NAME' => '', 
		'AWS_CERTIFICATE_AUTHORITY' => '', 
		'AWS_DEFAULT_CACHE_CONFIG' => '', 
		'AWS_MFA_SERIAL' => '', 
		'AWS_CLOUDFRONT_KEYPAIR_ID' => '', 
		'AWS_CLOUDFRONT_PRIVATE_KEY_PEM' => '', 
		'AWS_ENABLE_EXTENSIONS' => true
	);

	function credentials_valid() {
		foreach (array_keys(Module_Cloud::CLOUD_EC2_GLOBALS) as $name => $default) {
			if (zesk::get($name)) {
			}
		}
	}

	function has_feature($feature) {
		throw new Exception_Unimplemented(__CLASS__ . "::" . __METHOD__ . "()");
		return false;
	}

	function features() {
		throw new Exception_Unimplemented(__CLASS__ . "::" . __METHOD__ . "()");
		return false;
	}
}
	

