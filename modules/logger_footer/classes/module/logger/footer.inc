<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Module_Logger_Footer extends Module implements Interface_Module_Head, Interface_Module_Foot, Logger\Handler {
	/**
	 * Log messages received
	 *
	 * @var array
	 */
	private $log = [];

	/**
	 *
	 * {@inheritDoc}
	 * @see Module::initialize()
	 */
	public function initialize(): void {
		parent::initialize();
		if (!$this->application->console()) {
			$this->application->logger->register_handler(__CLASS__, $this);
		}
	}

	/**
	 * @param array $args
	 */
	public function log($message, array $args = null): void {
		$this->log[] = $args ?? [];
	}

	/**
	 * Is the Logger_Footer enabled?
	 *
	 * @return boolean
	 */
	public function enabled() {
		return !$this->option_bool('disabled', !$this->application->development());
	}

	/**
	 * Implements </html>
	 *
	 * @return string
	 */
	public function hook_foot(Request $request, Response $response, Template $template) {
		$result = [];
		if ($this->enabled() && count($this->log) > 0) {
			$result[] = HTML::tag_open("div", "#logger_footer");
			$result[] = HTML::tag_open("ul");
			$this->log[] = [
				"_level_string" => "info",
				"_formatted" => sprintf("%.3f", microtime(true) - $this->application->initialization_time()) . " seconds elapsed",
			];
			foreach ($this->log as $entry) {
				$level_class = strtolower($entry['_level_string']);
				$label_class = avalue([
					"debug" => "default",
					"notice" => "success",
					"error" => "danger",
				], $level_class, $level_class);
				$result[] = HTML::tag('li', ".level-$level_class", HTML::tag("label", ".label .label-$label_class", $entry['_level_string']) . " " . nl2br($entry['_formatted']));
			}
			$result[] = HTML::tag_close("ul");
			$result[] = HTML::tag_close("div");
		}
		echo implode("\n", $result);
	}

	/**
	 * Implement Module::head
	 *
	 * @param Request $request
	 * @param Response $response
	 */
	public function hook_head(Request $request, Response $response, Template $template): void {
		if ($this->enabled() && count($this->log) > 0) {
			$response->jquery();
			$share = [
				'share' => true,
			];
			$response->javascript('/share/zesk/js/zesk.js', $share);
			$response->javascript('/share/logger_footer/js/logger_footer.js', $share);
			$response->css('/share/logger_footer/css/logger_footer.css', $share);
		}
	}
}
