<?php

class Domain_Suffix extends Object {
	public function cron_month() {
		$url = 'http://www.iana.org/domains/root/db';
		
		list($binary) = File_Cache::from_url($url);
		$content = file_get_contents($binary);
		
		echo $content;
	}
}
