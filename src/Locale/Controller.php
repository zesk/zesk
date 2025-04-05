<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk\Locale;

use zesk\Exception\ClassNotFound;
use zesk\HTTP;
use zesk\JavaScript;
use zesk\Request;
use zesk\Response;

/**
 * @see Locale
 * @author kent
 *
 */
class Controller extends \zesk\Controller
{
	protected array $argumentMethods = [
	];

	protected array $actionMethods = [
		'action_{METHOD}_js',
	];

	protected array $beforeMethods = [
	];

	protected array $afterMethods = [
	];

	/**
	 * Allow API usage
	 *
	 * @param Request $request
	 * @param Response $response
	 * @return Response
	 */
	public function action_OPTIONS_js(Request $request, Response $response): Response
	{
		assert($request->method() === HTTP::METHOD_OPTIONS);
		return $this->handleOPTIONS($response, 'js');
	}

	/**
	 *
	 */
	public function action_GET_js(Request $request, Response $response): Response
	{
		$app = $this->application;
		$code = $request->get('ll');
		$locales = [];
		if (empty($code)) {
			$locales = [
				$app->locale,
			];
			$code = $app->locale->id();
		} else {
			$codes = array_unique(toList($code));
			foreach ($codes as $code) {
				try {
					$locales[$code] = Reader::factory($app->localePath(), $code)->locale($app);
				} catch (ClassNotFound) {
					// pass
				}
			}
		}

		$translations = [];
		foreach ($locales as $id => $locale) {
			/* @var $locale Locale */
			$translations[$id] = $locale->translations();
		}
		$load_lines = [];
		foreach ($translations as $id => $tt) {
			if (count($tt) > 0) {
				$load_lines[] = 'exports.Locale.load(' . JavaScript::arguments($code, $tt) . ');';
			} else {
				$load_lines[] = "/* No translations for $id */";
			}
		}
		$response->setContentType('text/javascript');
		$load_lines[] = 'exports.Locale.locale(' . JavaScript::arguments($app->locale->id()) . ');';

		$load_lines = implode("\n\t", $load_lines);
		$content = "/* elapsed: {page-render-time}, is_cached: {page-is-cached} */\n";
		$content .= "(function (exports) {\n\t$load_lines\n}(this));\n";

		$response->setCacheFor($this->optionInt('cache_seconds', 600));
		$response->content = $content;
		return $response;
	}
}
