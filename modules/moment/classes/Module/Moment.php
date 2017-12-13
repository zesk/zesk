<?php
/**
 * 
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class Module_Moment extends Module_JSLib implements Interface_Module_Head {
	/**
	 * 
	 * @var array
	 */
	protected $javascript_paths = array(
		"/share/moment/moment.js",
		"/share/moment/moment-with-locales.js"
	);
	protected $class_aliases = array(
		"Module_Moment" => __CLASS__
	);
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Module::initialize()
	 */
	public function initialize() {
		if ($this->option_bool('timezone_support')) {
			$this->javascript_paths[] = '/share/moment/moment-timezone.js';
			$this->javascript_paths[] = '/share/moment/moment-timezone-with-data-2010-2020.js';
			$this->javascript_paths[] = '/share/moment/moment-timezone-with-data.js';
		}
		parent::initialize();
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Module_JSLib::hook_head()
	 */
	public function hook_head(Request $request, Response_Text_HTML $response, Template $template) {
		$locale = Locale::current();
		$language = Locale::language($locale);
		$this->jquery_ready[] = "try {\n\tmoment.locale(" . JavaScript::arguments(array(
			$locale,
			$language
		)) . ");\n} catch (e) {\n\twindow.zesk && window.zesk.log(\"Moment locale $locale failed to load\");\n}";
		parent::hook_head($request, $response, $template);
	}
}
