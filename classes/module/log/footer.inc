<?php
class Module_Log_Footer extends Module_Log implements Module_Interface_Foot, Module_Interface_Head {
	private $log = array();
	
	/**
	 * Implements Module::log_send()
	 *
	 * @param array $args        	
	 */
	public function hook_log_send(array $args) {
		if (zesk::$console) {
			return;
		}
		// Potential memory issue
		$this->log[] = $args;
	}
	
	/**
	 * Is the FooterLog enabled?
	 *
	 * @return boolean
	 */
	public function enabled() {
		return $this->option_bool('enabled', $this->application->development());
	}
	/**
	 * Implements </html>
	 *
	 * @return string
	 */
	public function hook_foot(Request $request, Response_HTML $response, Template $template) {
		$result = "";
		if (self::enabled() && count($this->log) > 0) {
			$result .= html::tag_open("div", "#zesk-errors");
			$result .= html::tag_open("ul");
			foreach ($this->log as $entry) {
				$level_class = strtolower($entry['_level_string']);
				$label_class = avalue(array(
					"debug" => "default",
					"notice" => "success",
					"error" => "danger"
				), $level_class, $level_class);
				$result .= html::tag('li', ".level-$level_class", html::tag("label", ".label .label-$label_class", $entry['_level_string']) . $entry['_formatted']);
			}
			$result .= html::tag_close("ul");
			$result .= html::tag_close("div");
		}
		echo $result;
	}
	
	/**
	 * Implement Module::head
	 *
	 * @param Request $request        	
	 * @param Response_HTML $response        	
	 */
	public function hook_head(Request $request, Response_HTML $response, Template $template) {
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
