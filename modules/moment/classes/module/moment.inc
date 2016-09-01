<?php
class Module_Moment extends Module_JSLib implements Module_Interface_Head {
	protected $javascript_paths = array(
		"/share/moment/moment.js",
		"/share/moment/moment-with-locales.js"
	);
	public function initialize() {
		if ($this->option_bool('timezone_support')) {
			$this->javascript_paths[] = '/share/moment/moment-timezone.js';
			$this->javascript_paths[] = '/share/moment/moment-timezone-with-data-2010-2020.js';
			$this->javascript_paths[] = '/share/moment/moment-timezone-with-data.js';
		}
		parent::initialize();
	}
	public function hook_head(Request $request, Response_HTML $response, Template $template) {
		$locale = zesk\Locale::current();
		$language = zesk\Locale::language($locale);
		$this->jquery_ready[] = "try {\n\tmoment.locale(" . js::arguments(array(
			$locale,
			$language
		)) . ");\n} catch (e) {\n\twindow.zesk && window.zesk.log(\"Moment locale $locale failed to load\");\n}";
		parent::hook_head($request, $response, $template);
	}
}
