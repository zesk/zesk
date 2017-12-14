<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/View/Graph.php $
 * @package zesk
 * @subpackage widgets
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 * Created on Fri Oct 09 19:22:21 EDT 2009 19:22:21
 */
namespace zesk;

class View_Graph extends View {
	function render() {
		$this->response->cdn_jquery();
		$this->response->javascript('/share/zesk/js/zesk.js', array(
			'weight' => 'first'
		));
		$this->response->javascript('/share/zesk/jquery/flot/jquery.flot.js');
		$this->response->javascript('/share/zesk/jquery/flot/excanvas.pack.js', array(
			'browser' => "ie"
		));
		$this->response->javascript('/share/zesk/jquery/zesk.flot.js');
		return parent::render();
	}
}


