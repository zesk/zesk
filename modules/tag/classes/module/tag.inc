<?php
class Module_Tag extends Module {
	protected $classes = array(
		'zesk\\Tag',
		'zesk\\Tag_Label'
	);
	
	/**
	 * Run once an hour on a single cluster machine
	 */
	public function hook_cron_cluster_hour() {
		zesk\Tag::cull($this->application);
	}
}