<?php
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class Module_Tag extends Module {
	protected $classes = array(
		'zesk\\Tag',
		'zesk\\Tag_Label'
	);
	
	/**
	 * Run once an hour on a single cluster machine
	 */
	public function hook_cron_cluster_hour() {
		Tag::cull($this->application);
	}
}
