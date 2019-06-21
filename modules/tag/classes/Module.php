<?php
/**
 * @package zesk-modules
 * @subpackage tag
 * @author kent
 * @copyright &copy; 2018 Market Acumen, Inc.
 */
namespace zesk\Tag;

/**
 *
 * @author kent
 *
 */
class Module extends \zesk\Module {
	protected $model_classes = array(
		Tag::class,
		Label::class,
	);

	/**
	 * Run once an hour on a single cluster machine
	 */
	public function hook_cron_cluster_hour() {
		Tag::cull($this->application);
	}
}
