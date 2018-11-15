<?php
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
    public function action_js() {
        $app = $this->application;
        $code = $this->request->get('ll');
        if (empty($code)) {
            $locales = array(
                $app->locale,
            );
            $code = $app->locale->id();
        } else {
            $codes = array_unique(to_list($code));
            foreach ($codes as $code) {
                $locales[$code] = Reader::factory($app->locale_path(), $code)->locale($app);
            }
        }
        
        $translations = array();
        foreach ($locales as $id => $locale) {
            /* @var $locale Locale  */
            $translations[$id] = $locale->translations();
        }
        $load_lines = array();
        foreach ($translations as $id => $tt) {
            if (count($tt) > 0) {
                $load_lines[] = 'exports.Locale.load(' . JavaScript::arguments($code, $tt) . ');';
            } else {
                $load_lines[] = "/* No translations for $id */";
            }
        }
        $this->response->content_type("text/javascript");
        $load_lines[] = 'exports.Locale.locale(' . JavaScript::arguments($app->locale->id()) . ');';
        
        $load_lines = implode("\n\t", $load_lines);
        $content = "/* elapsed: {page-render-time}, is_cached: {page-is-cached} */\n";
        $content .= "(function (exports) {\n\t$load_lines\n}(this));\n";
        
        $this->response->cache_for($this->option_integer("cache_seconds", 600));
        $this->response->content = $content;
    }
}
