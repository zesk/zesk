<?php
class Module_File_Cache extends zesk\Module {
	public function hook_cron_hour() {
		// Expire cache
		foreach (File_Cache::iterator($this->application) as $url => $settings) {
			$this->application->logger->debug("{url} found in cache TODO", array(
				"url" => $url
			));
		}
	}
	function settings() {
		return array(
			"File_Cache::path" => array(
				"label" => __("Path"),
				"description" => __("Directory for file cache database"),
				"type" => "dir"
			),
			"File_Cache::ttl" => array(
				"label" => __("Time to live"),
				"description" => "Number of seconds",
				"units" => "seconds",
				"type" => "integer"
			)
		);
	}
}
