<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage controller
 * @author kent
 * @copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Controller_Search extends Controller_Theme {
	/**
	 *
	 * @throws Exception_Class_NotFound
	 * @return string
	 */
	protected function action_index() {
		$query = $this->request->get($this->option('search_query_variable', 'q'));
		$results = [];
		$total = $shown = 0;
		foreach ($this->optionIterable('search_classes') as $class) {
			try {
				if (class_exists($class)) {
					$object = $this->widgetFactory($class);
					$method = 'controller_search';
					if (method_exists($object, $method)) {
						$result = call_user_func([
							$object,
							$method,
						], $query);
						if (is_array($result)) {
							$results[$class] = $result;
							$shown += $result['shown'];
							$total += $result['total'];
						}
					} else {
						$this->application->logger->error('Controller_Search::action_index {class} does not have method {method}', [
							'class' => $class,
							'method' => $method,
						]);
					}
				} else {
					throw new Exception_Class_NotFound($class);
				}
			} catch (Exception_Class_NotFound $e) {
				$this->application->hooks->call('exception', $e);
				$this->application->logger->error('Controller_Search::action_index {class} does not exist', [
					'class' => $class,
				]);

				continue;
			}
		}
		return $this->theme($total === 0 ? 'search/no-results' : 'search/results', [
			'raw_query' => $query,
			'query' => htmlspecialchars($query),
			'theme_search_form' => 'block/search',
			'results' => $results,
		]);
	}
}
