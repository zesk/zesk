<?php
/**
 * 
 */
use zesk\JavaScript;
use zesk\Locale;

/**
 * 
 * @author kent
 *
 */
class Controller_Locale extends zesk\Controller {
	/**
	 * 
	 */
	function action_js() {
		$locale = $this->request->get('ll');
		if (empty($locale)) {
			$locale = Locale::current();
		}
		$locale = to_list($locale);
		$locales = array();
		foreach ($locale as $l) {
			$tt = Locale::load($l);
			if (count($tt) !== 0) {
				$locales[$l] = $tt;
			} else {
				$lang = Locale::language($l);
				if ($lang !== $l) {
					$locales[$lang] = Locale::load($lang);
				}
			}
		}
		$load_lines = array();
		$load_lines[] = 'exports.Locale.locale(' . JavaScript::arguments(Locale::current()) . ');';
		foreach ($locales as $code => $tt) {
			if (count($tt) > 0) {
				$load_lines[] = 'exports.Locale.load(' . JavaScript::arguments($code, $tt) . ');';
			}
		}
		$this->response->content_type("text/javascript");
		if (count($load_lines) === 0) {
			$content = "/* No translation tables found for $locale */";
		} else {
			$load_lines = implode("\n\t", $load_lines);
			$content = "/* elapsed: {page-render-time}, is_cached: {page-is-cached} */\n(function (exports) {\n\t$load_lines\n}(this));";
		}
		$this->response->cache_for($this->option_integer("cache_seconds", 600));
		$this->response->content = $content;
	}
}
