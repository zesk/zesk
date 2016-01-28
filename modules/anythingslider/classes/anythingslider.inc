<?php
/**
 *
 * @version $URL: https://code.marketacumen.com/zesk/trunk/modules/anythingslider/classes/anythingslider.inc $
 */
class AnythingSlider extends Module implements Module_Interface_Head {
	public function add(Response_HTML $response, $id, $options) {
		$options = to_array($options, array());
		$response->jquery("\$('$id').anythingSlider(" . json::encode($options) . ");");
	}
	public function hook_head(Request $request, Response_HTML $response, Template $template) {
		$response->jquery();
		if ($this->application->development()) {
			$response->cdn_javascript('/share/anythingslider/js/jquery.anythingslider.js');
		} else {
			$response->cdn_javascript('/share/anythingslider/js/jquery.anythingslider.min.js');
		}
		$response->cdn_javascript('/share/anythingslider/js/jquery.easing.1.2.js');
		$response->cdn_css('/share/anythingslider/css/anythingslider.css');
		$response->cdn_css('/share/anythingslider/css/animate.css');
	}
}
