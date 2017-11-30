<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/anythingslider/classes/anythingslider.inc $
 * @copyright &copy; 2016 Market Acumen, Inc.
 */
use zesk\JSON;

/**
 * 
 * @author kent
 *
 */
class AnythingSlider extends zesk\Module implements \zesk\Interface_Module_Head {
	
	/**
	 * 
	 * @param zesk\Response_Text_HTML $response
	 * @param unknown $id
	 * @param unknown $options
	 */
	public function add(zesk\Response_Text_HTML $response, $id, $options) {
		$options = to_array($options, array());
		$response->jquery("\$('$id').anythingSlider(" . JSON::encode($options) . ");");
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \zesk\Interface_Module_Head::hook_head()
	 */
	public function hook_head(zesk\Request $request, zesk\Response_Text_HTML $response, zesk\Template $template) {
		$response->jquery();
		if ($this->application->development()) {
			$response->javascript('/share/anythingslider/js/jquery.anythingslider.js');
		} else {
			$response->javascript('/share/anythingslider/js/jquery.anythingslider.min.js');
		}
		$response->javascript('/share/anythingslider/js/jquery.easing.1.2.js');
		$response->css('/share/anythingslider/css/anythingslider.css');
		$response->css('/share/anythingslider/css/animate.css');
	}
}
