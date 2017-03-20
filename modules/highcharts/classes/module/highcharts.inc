<?php

/**
 * Highcharts module
 * 
 * @author kent
 *
 */
class Module_Highcharts extends zesk\Module implements zesk\Interface_Module_Head {
	public function hook_head(zesk\Request $request, zesk\Response_Text_HTML $response, zesk\Template $template) {
		if ($response->option_bool('Module_Highcharts')) {
			$this->response_apply($response);
		}
	}
	public function response_apply(zesk\Response_Text_HTML $response) {
		$response->jquery();
		if ($this->option_bool('debug', $this->application->development())) {
			$response->cdn_javascript("/share/highcharts/highcharts.src.js", array(
				'share' => true
			));
		} else {
			$response->cdn_javascript("/share/highcharts/highcharts.js", array(
				'share' => true
			));
		}
	}
}
