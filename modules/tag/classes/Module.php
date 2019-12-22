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
class Module extends \zesk\Module_JSLib {
	protected $model_classes = array(
		Label::class,
	);

	protected $javascript_paths = array(
		"/share/tag/js/jquery.tag.js" => [
			'share' => true,
		],
	);

	protected $css_paths = array(
		"/share/tag/css/tag.css" => [
			'share' => true,
		],
	);

	/**
	 * Run once an hour on a single cluster machine
	 */
	public function hook_cron_cluster_hour() {
		$this->application->orm_module();
	}

	/**
	 *
	 * @return mixed|number|array|\zesk\Tag\unknown
	 */
	public function list_labels() {
		$application = $this->application;
		$labels = $application->orm_registry(Label::class)
			->query_select()
			->order_by("X.name")
			->orm_iterator();
		$labels = $this->filter_labels($labels);
		return $labels;
	}

	/**
	 *
	 * @param unknown $items
	 * @return mixed|number|array|unknown
	 */
	public function filter_labels($items) {
		$result = $this->call_hook_arguments("filter_labels", array(
			$items,
		), $items);
		if (is_iterable($result)) {
			return $result;
		}
		$this->application->logger->warning("{class}::call_hook_arguments(\"filter_labels\") returned non-iterable {type} - nothing was filtered", [
			"class" => get_class($this),
			"type" => type($result),
		]);
		return $items;
	}
}
