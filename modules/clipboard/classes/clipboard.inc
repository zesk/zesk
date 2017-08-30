<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/clipboard/classes/clipboard.inc $
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 * Created on Tue Sep 22 15:59:22 EDT 2009 15:59:22
 */
namespace zesk;

/**
 * @todo move this to a template
 * @author kent
 *
 */
class clipboard {
	public static function output(Response_Text_HTML $response, $options = false) {
		static $player_id = 1;
		
		$x = new Options($options);
		
		$js_text = $x->option('text');
		$text_id = $x->option('text_id');
		$autofetch = intval($x->option_bool('autofetch'));
		$wrap_class = $x->option('class');
		$width = $x->option_integer('width', 20);
		$height = $x->option_integer('height');
		$get_flash_text = $x->option('get_flash_text', "<a href=\"http://www.macromedia.com/go/getflashplayer\">Get the Flash Player</a> to support copying to the clipboard.");
		
		if (empty($height)) {
			$height = ($width * 4) / 3;
		}
		$height = intval($height);
		$id = $player_id++;
		
		$response->jquery();
		$response->javascript("/share/zesk/js/swfobject.js");
		
		// TODO: CDN?
		$response->javascript("/clipboard/clipboard.js", ZESK_ROOT . '/share/zesk/widgets');
		
		$html = "<div id=\"clipboard_$id\">$get_flash_text</div>";
		if ($wrap_class) {
			$html = HTML::tag("div", array(
				"class" => $wrap_class
			), $html);
		}
		
		$json = array();
		$json['autofetch'] = $autofetch;
		if ($text_id) {
			$json['id'] = $text_id;
		}
		if ($js_text) {
			/* Fucking swfobject idiots */
			$json['*clipboard'] = $js_text . '.replace(/%/g,\'%25\').replace(/&/g,\'%26\').replace(/\+/g,\'%2B\')';
		}
		$js = "";
		$js .= "var fv$id = " . JSON::encode($json) . ";\n";
		$js .= "var fp$id = swfobject.embedSWF('/clipboard/clipboard.swf','clipboard_$id',$width,$height,'7',null,fv$id);\n";
		
		$response->jquery($js);
		
		return $html;
	}
}
