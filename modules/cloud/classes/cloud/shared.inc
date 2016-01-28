<?php

/**
 * Represents a shared, non-root system with web access
 * 
 * @author kent
 */
class Cloud_Shared implements Cloud {

	function credentials_valid() {
		return true;
	}

	function features() {
		return array(
			self::feature_storage => system::volume_info()
		);
	}

	function has_feature($feature) {
		return array_key_exists($feature, self::features());
	}
}
