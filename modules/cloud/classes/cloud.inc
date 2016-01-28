<?php

interface Cloud {
	const feature_package_management = 'package';
	const feature_storage = 'storage';
	const feature_memory = 'memory';
	
	function credentials_valid();
	
	function has_feature($feature);
	
	function features();
	
	
}
