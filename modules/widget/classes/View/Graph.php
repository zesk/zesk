<?php
/**
 * @package zesk
 * @subpackage widgets
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 * Created on Fri Oct 09 19:22:21 EDT 2009 19:22:21
 */
namespace zesk;

class View_Graph extends View {
    public function render() {
        $html = $this->response()->html();
        $html->jquery();
        $html->javascript('/share/zesk/js/zesk.js', array(
            'weight' => 'first',
        ));
        $html->javascript('/share/zesk/jquery/flot/jquery.flot.js');
        $html->javascript('/share/zesk/jquery/flot/excanvas.pack.js', array(
            'browser' => "ie",
        ));
        $html->javascript('/share/zesk/jquery/zesk.flot.js');
        return parent::render();
    }
}
