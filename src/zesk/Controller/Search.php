<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage controller
 * @author kent
 * @copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Controller;

use zesk\Controller\ThemeController;
use zesk\Exception\ClassNotFound;
use zesk\Request;

/**
 *
 * @author kent
 *
 */
class Search extends ThemeController {
	/**
	 *
	 * @return string
	 * @throws ClassNotFound|Redirect
	 */
	protected function action_index(Request $request): string {
		$query = $request->get($this->option('search_query_variable', 'q'));
		$results = [];
		$total = $shown = 0;
		foreach ($this->optionIterable('search_classes') as $class) {
			$object = $this->application->factory($class);
			$method = 'controllerSearch';
			if (method_exists($object, $method)) {
				$result = call_user_func([
					$object, $method,
				], $query);
				if (is_array($result)) {
					$results[$class] = $result;
					$shown += $result['shown'];
					$total += $result['total'];
				}
			} else {
				$this->application->error('Controller_Search::action_index {class} does not have method {method}', [
					'class' => $class, 'method' => $method,
				]);
			}
		}
		return $this->theme($total === 0 ? 'search/no-results' : 'search/results', [
			'raw_query' => $query, 'query' => htmlspecialchars($query), 'theme_search_form' => 'block/search',
			'results' => $results,
		]);
	}
}
