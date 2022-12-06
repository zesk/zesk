<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

use aws\classes\Module;

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
	protected $javascript_paths = [
		'/share/moment/moment.js',
		'/share/moment/moment-with-locales.js',
	];

	protected $class_aliases = [
		'Module_Moment' => __CLASS__,
	];

	/**
	 *
	 * {@inheritDoc}
	 * @see Module::initialize()
	 */
	public function initialize(): void {
		if ($this->optionBool('timezone_support')) {
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
	public function hook_head(Request $request, Response $response, Template $template): void {
		$locale = $this->application->locale;
		$this->jquery_ready[] = "try {\n\tmoment.locale(" . JavaScript::arguments([
			$locale->id(),
			$locale->language(),
		]) . ");\n} catch (e) {\n\twindow.zesk && window.zesk.log(\"Moment locale " . $locale->id() . " failed to load\");\n}";
		parent::hook_head($request, $response, $template);
	}
}
