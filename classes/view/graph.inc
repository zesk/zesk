<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/view/graph.inc $
 * @package zesk
 * @subpackage widgets
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 * Created on Fri Oct 09 19:22:21 EDT 2009 19:22:21
 */
class View_Graph extends View {
	function render() {
		$this->response->cdn_jquery();
		$this->response->cdn_javascript('/share/zesk/js/zesk.js', array('weight' => 'first'));
		$this->response->cdn_javascript('/share/zesk/jquery/flot/jquery.flot.js');
		$this->response->cdn_javascript('/share/zesk/jquery/flot/excanvas.pack.js', array('browser' => "ie"));
		$this->response->cdn_javascript('/share/zesk/jquery/zesk.flot.js');
		return parent::render();
	}
}


