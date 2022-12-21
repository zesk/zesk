<?php declare(strict_types=1);
/**
 *
 */
namespace zesk\Locale;

use zesk\JavaScript;

/**
 * @see Locale
 * @author kent
 *
 */
class Controller extends \zesk\Controller {
	/**
	 *
	 */
	public function action_js(): void {
		$app = $this->application;
		$code = $this->request->get('ll');
		$locales = [];
		if (empty($code)) {
			$locales = [
				$app->locale,
			];
			$code = $app->locale->id();
		} else {
			$codes = array_unique(toList($code));
			foreach ($codes as $code) {
				$locales[$code] = Reader::factory($app->localePath(), $code)->locale($app);
			}
		}

		$translations = [];
		foreach ($locales as $id => $locale) {
			/* @var $locale Locale  */
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
		$this->response->setContentType('text/javascript');
		$load_lines[] = 'exports.Locale.locale(' . JavaScript::arguments($app->locale->id()) . ');';

		$load_lines = implode("\n\t", $load_lines);
		$content = "/* elapsed: {page-render-time}, is_cached: {page-is-cached} */\n";
		$content .= "(function (exports) {\n\t$load_lines\n}(this));\n";

		$this->response->setCacheFor($this->optionInt('cache_seconds', 600));
		$this->response->content = $content;
	}
}
