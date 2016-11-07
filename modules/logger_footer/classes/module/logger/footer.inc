<?php
use zesk\HTML;

/**
 * 
 * @author kent
 *
 */
class Module_Logger_Footer extends Module implements zesk\Interface_Module_Head, zesk\Interface_Module_Foot, zesk\Logger\Handler {
	/**
	 * Log messages received
	 * 
	 * @var array
	 */
	private $log = array();
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Module::initialize()
	 */
	public function initialize() {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		parent::initialize();
		if (!$zesk->console) {
			$zesk->logger->register_handler(__CLASS__, $this);
		}
	}
	
	/**
	 * @param array $args
	 */
	public function log($message, array $args) {
		$this->log[] = $args;
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
	public function hook_foot(zesk\Request $request, zesk\Response_Text_HTML $response, zesk\Template $template) {
		$result = array();
		if ($this->enabled() && count($this->log) > 0) {
			global $zesk;
			/* @var $zesk \zesk\Kernel */
			$result[] = HTML::tag_open("div", "#zesk-errors");
			$result[] = HTML::tag_open("ul");
			$this->log[] = array(
				"_level_string" => "info",
				"_formatted" => sprintf("%.3f", microtime(true) - $zesk->initialization_time) . " seconds elapsed"
			);
			foreach ($this->log as $entry) {
				$level_class = strtolower($entry['_level_string']);
				$label_class = avalue(array(
					"debug" => "default",
					"notice" => "success",
					"error" => "danger"
				), $level_class, $level_class);
				$result[] = HTML::tag('li', ".level-$level_class", HTML::tag("label", ".label .label-$label_class", $entry['_level_string']) . " " . $entry['_formatted']);
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
	 * @param zesk\Response_Text_HTML $response
	 */
	public function hook_head(zesk\Request $request, zesk\Response_Text_HTML $response, zesk\Template $template) {
		if ($this->enabled() && count($this->log) > 0) {
			$response->jquery();
			$response->cdn_javascript('/share/zesk/js/zesk.js', array(
				'share' => true
			));
			$response->cdn_javascript('/share/zesk/js/zesk-debug.js', array(
				'share' => true
			));
			$response->cdn_css('/share/zesk/css/zesk-debug.css', array(
				'share' => true
			));
		}
	}
}
	
