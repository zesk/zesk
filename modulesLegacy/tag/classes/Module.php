<?php declare(strict_types=1);
/**
 * @package zesk-modules
 * @subpackage tag
 * @author kent
 * @copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk\Tag;

/**
 *
 * @author kent
 *
 */
class Module extends \zesk\Module_JSLib {
	protected array $modelClasses = [
		Label::class,
	];

	protected $javascript_paths = [
		'/share/tag/js/jquery.tag.js' => [
			'share' => true,
		],
	];

	protected $css_paths = [
		'/share/tag/css/tag.css' => [
			'share' => true,
		],
	];

	/**
	 * Run once an hour on a single cluster machine
	 */
	public function hook_cron_cluster_hour(): void {
		$this->application->orm_module();
	}

	/**
	 *
	 * @return mixed|number|array|\zesk\Tag\unknown
	 */
	public function list_labels() {
		$application = $this->application;
		$labels = $application->ormRegistry(Label::class)
			->querySelect()
			->order_by('X.name')
			->ormIterator();
		$labels = $this->filter_labels($labels);
		return $labels;
	}

	/**
	 *
	 * @param unknown $items
	 * @return mixed|number|array|unknown
	 */
	public function filter_labels($items) {
		$result = $this->callHookArguments('filter_labels', [
			$items,
		], $items);
		if (is_iterable($result)) {
			return $result;
		}
		$this->application->logger->warning('{class}::callHookArguments("filter_labels") returned non-iterable {type} - nothing was filtered', [
			'class' => get_class($this),
			'type' => type($result),
		]);
		return $items;
	}
}
