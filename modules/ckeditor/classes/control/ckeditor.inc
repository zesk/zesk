<?php
namespace zesk;

/**
 * @package zesk
 * @subpackage control
 */
class Control_CKEditor extends Control_Text {
	private $FCKEditor = null;
	
	/*===========================================================================*\
	 * Control Methods
	 \*---------------------------------------------------------------------------*/
	/**
	 * Instance of FCKeditor for this object
	 *
	 * @return FCKeditor
	 */
	private function fck_path() {
		return rtrim($this->option("fck_path", $this->option('fck_path', '/share/ckeditor/widgets/fckeditor/')), '/') . '/';
	}
	private function fck() {
		if ($this->FCKEditor) {
			return $this->FCKEditor;
		}
		
		$fck = $this->application->objects->factory('FCKeditor', $this->column());
		// N.B. FCKEditor must be hosted locally
		$fck->BasePath = $this->fck_path();
		list($rows, $cols) = $this->dimensions();
		$fck->Height = $rows * 20;
		$fck->Width = ($cols * 10) . "px";
		$this->FCKEditor = $fck;
		return $fck;
	}
	public final function render(Model $data) {
		// Important: FCKEditor can not be hosted on another domain
		$this->response->javascript($this->fck_path() . "fckeditor.js");
		$this->response->javascript($this->fck_path() . "editor/fckconfig-simple.js");
		
		$column_name = $this->column();
		$fck = $this->fck();
		return $fck->CreateHtml($this->value($data));
	}
	public final function dimensions() {
		$rows = $this->option_integer("rows", 5);
		$cols = $this->option_integer("cols", 80);
		
		$maxRows = $this->option_integer("rows_max", 64);
		$maxCols = $this->option_integer("cols_max", 200);
		
		if ($rows > $maxRows) {
			$rows = $maxRows;
		}
		if ($cols > $maxCols) {
			$cols = $maxCols;
		}
		return array(
			$rows,
			$cols
		);
	}
	private function clean($x) {
		$x = HTML::clean_tags_without_attributes("a", $x);
		//		echo "<h1>---original-------</h1>\n";
		//		dump($x);
		$x = self::entities_clean($x);
		//		echo "<h1>---entities_clean-------</h1>\n";
		//		dump($x);
		$x = HTML::clean_tags_attributes($x, "style;href;target", false);
		
		$x = HTML::clean_style_attributes($x, "font-weight;font-style;margin-left;text-align", false);
		
		//		echo "<h1>---HTML::clean_tags_attributes-------</h1>\n";
		//		dump($x);
		
		$allowed_tags = $this->option_list("allowed_tags", "strong;em;ul;li;h1;h2;h3;h4;h5;pre;a;blockquote;p;br;span;div");
		$x = HTML::clean_tags($x, $allowed_tags);
		//		echo "<h1>---HTML::clean_tags-------</h1>\n";
		//		dump($x);
		
		$convert_tags = array(
			"<br>" => "<br />"
		);
		$x = tr($x, $convert_tags);
		//		echo "<h1>---replace_map-------</h1>\n";
		//		dump($x);
		
		$x = HTML::trim_white_space($x);
		
		return $x;
	}
	private function autoLink($html) {
		$x = HTML::remove_tags(explode(";", "a;img;link;style;pre"), $html, true);
		
		$urls = false;
		if (preg_match_all('/[^"]([a-zA-Z]+[:\/\/]+[A-Za-z0-9\-_]+\\.+[A-Za-z0-9\.\/%&=\?\-_]+)/i', $x, $urls)) {
			foreach ($urls as $url) {
				$html = str_replace($url[1], HTML::a($url[1], $url[1]), $html);
			}
		}
		return $html;
	}
	private static function entities_clean($x) {
		$ents = array(
			chr(160) => "&nbsp;",
			chr(161) => "&iexcl;",
			chr(162) => "&cent;",
			chr(163) => "&pound;",
			chr(164) => "&curren;",
			chr(165) => "&yen;",
			chr(166) => "&brvbar;",
			chr(167) => "&sect;",
			chr(168) => "&uml;",
			chr(169) => "&copy;",
			chr(170) => "&ordf;",
			chr(171) => "&laquo;",
			chr(172) => "&not;",
			chr(173) => "&shy;",
			chr(174) => "&reg;",
			chr(175) => "&macr;",
			chr(176) => "&deg;",
			chr(177) => "&plusmn;",
			chr(178) => "&sup2;",
			chr(179) => "&sup3;",
			chr(180) => "&acute;",
			chr(181) => "&micro;",
			chr(182) => "&para;",
			chr(183) => "&middot;",
			chr(184) => "&cedil;",
			chr(185) => "&sup1;",
			chr(186) => "&ordm;",
			chr(187) => "&raquo;",
			chr(188) => "&frac14;",
			chr(189) => "&frac12;",
			chr(190) => "&frac34;",
			chr(191) => "&iquest;",
			chr(192) => "&Agrave;",
			chr(193) => "&Aacute;",
			chr(194) => "&Acirc;",
			chr(195) => "&Atilde;",
			chr(196) => "&Auml;",
			chr(197) => "&Aring;",
			chr(198) => "&AElig;",
			chr(199) => "&Ccedil;",
			chr(200) => "&Egrave;",
			chr(201) => "&Eacute;",
			chr(202) => "&Ecirc;",
			chr(203) => "&Euml;",
			chr(204) => "&Igrave;",
			chr(205) => "&Iacute;",
			chr(206) => "&Icirc;",
			chr(207) => "&Iuml;",
			chr(208) => "&ETH;",
			chr(209) => "&Ntilde;",
			chr(210) => "&Ograve;",
			chr(211) => "&Oacute;",
			chr(212) => "&Ocirc;",
			chr(213) => "&Otilde;",
			chr(214) => "&Ouml;",
			chr(215) => "&times;",
			chr(216) => "&Oslash;",
			chr(217) => "&Ugrave;",
			chr(218) => "&Uacute;",
			chr(219) => "&Ucirc;",
			chr(220) => "&Uuml;",
			chr(221) => "&Yacute;",
			chr(222) => "&THORN;",
			chr(223) => "&szlig;",
			chr(224) => "&agrave;",
			chr(225) => "&aacute;",
			chr(226) => "&acirc;",
			chr(227) => "&atilde;",
			chr(228) => "&auml;",
			chr(229) => "&aring;",
			chr(230) => "&aelig;",
			chr(231) => "&ccedil;",
			chr(232) => "&egrave;",
			chr(233) => "&eacute;",
			chr(234) => "&ecirc;",
			chr(235) => "&euml;",
			chr(236) => "&igrave;",
			chr(237) => "&iacute;",
			chr(238) => "&icirc;",
			chr(239) => "&iuml;",
			chr(240) => "&eth;",
			chr(241) => "&ntilde;",
			chr(242) => "&ograve;",
			chr(243) => "&oacute;",
			chr(244) => "&ocirc;",
			chr(245) => "&otilde;",
			chr(246) => "&ouml;",
			chr(247) => "&divide;",
			chr(248) => "&oslash;",
			chr(249) => "&ugrave;",
			chr(250) => "&uacute;",
			chr(251) => "&ucirc;",
			chr(252) => "&uuml;",
			chr(253) => "&yacute;",
			chr(254) => "&thorn;",
			chr(255) => "&yuml;"
		);
		$x = str_replace(array_keys($ents), array_values($ents), $x);
		$x = str_replace("(TM)", "&#0153;", $x);
		$x = str_replace("(C)", "&copy;", $x);
		return $x;
	}
	
	/*
	 RTESafe:
	 From Cross-Browser Rich Text Editor
	 http://www.kevinroth.com/rte/demo.htm
	 */
	static private function RTESafe($strText) {
		//returns safe code for preloading in the RTE
		$tmpString = trim($strText);
		
		//convert all types of single quotes
		$tmpString = str_replace(chr(145), chr(39), $tmpString);
		$tmpString = str_replace(chr(146), chr(39), $tmpString);
		$tmpString = str_replace("'", "&#39;", $tmpString);
		
		//convert all types of double quotes
		$tmpString = str_replace(chr(147), chr(34), $tmpString);
		$tmpString = str_replace(chr(148), chr(34), $tmpString);
		
		//replace carriage returns & line feeds
		$tmpString = str_replace(chr(10), " ", $tmpString);
		$tmpString = str_replace(chr(13), " ", $tmpString);
		
		$tmpString = str_replace("<", "&lt;", $tmpString);
		$tmpString = str_replace(">", "&gt;", $tmpString);
		$tmpString = str_replace("\n", "\\n", $tmpString);
		
		return $tmpString;
	}
	static private function noscriptRTESafe($strText) {
		$tmpString = trim($strText);
		
		//convert all types of single quotes
		$tmpString = htmlspecialchars($tmpString);
		
		return $tmpString;
	}
	
	//	function _convertSingles($string)
	//	{
	//		$result = CHTML::parseTags($string);
	//		if (!is_array($result))
	//			return $string;
	//		foreach ($result as $tag => $attrs) {
	//			switch ($tag) {
	//				case "hr":
	//				case "br":
	//					$result = "<$tag />";
	//					break;
	//				case "img":
	//					if (is_array($attrs)) {
	//						$attrs = arr::include_exclude($attrs, array("width", "height", "align"));
	//						$result = HTML::tag($tag, $attrs, null);
	//					}
	//					break;
	//			}
	//			$replace[] = $result;
	//		}
	//		return str_replace($search, $replace, $string);
	//	}
	
	//	function modelFromRTE($string)
	//	{
	//		/*
	//		<P align=justify><IMG style="WIDTH: 156px; HEIGHT: 63px" height=114 src="http://zesk/images/ioflux-logo.gif" width=96 align=right></P>
	//		<P><STRONG>Bold text.</STRONG></P>
	//		<P><EM>Italic text.</EM></P>
	//		<P><U>Underlined.</U></P>
	//		<P align=left>Left aligned</P>
	//		<P align=center>Center.</P>
	//		<P align=right>Right.</P>
	//		<P align=justify>Justified text.</P>
	//		<P>Horizontal Rule:</P>
	//		<HR>
	//
	//		<OL>
	//		<LI>Numbered list.<IMG src="http://zesk/images/ioflux-logo.gif"></LI>
	//		<LI>Number 2</LI>
	//		<LI>Number 3</LI></OL>
	//		<UL>
	//		<LI>Bullet list.</LI>
	//		<LI>Bullet list.</LI></UL>
	//		<BLOCKQUOTE dir=ltr style="MARGIN-RIGHT: 0px">
	//		<P>Blockquoted</P></BLOCKQUOTE>
	//		<P><FONT color=#ff9966>Font color.</FONT></P>
	//		<P><FONT style="BACKGROUND-COLOR: #ff9966">Background color.</FONT></P>
	//		<P><A href="http://www.zesk.com/">Link.</A></P>
	//		<P>Image.</P>
	//		<P>Special chars: &amp; _ ; &lt; &gt;</P>
	//		*/
	//		$matches = false;
	//		if (preg_match_all('/<([\w]+)([^>]*)>(.*)<\/\\1>/U', $string, $matches, PREG_SET_ORDER)) {
	//			$search		= array();
	//			$replace	= array();
	//			foreach ($matches as $match) {
	//				$search		= $match[0];
	//				$tag		= strtolower($match[1]);
	//				$attrs		= CHTML::parseAttributes($match[2]);
	//				$content	= $this->modelFromRTE($match[3]);
	//				$result		= "";
	//				switch ($tag) {
	//					case "h1":
	//					case "h2":
	//					case "h3":
	//					case "h4":
	//					case "h5":
	//					case "em":
	//					case "u":
	//					case "li":
	//					case "ol":
	//					case "blockquote":
	//						$result = CHTML::beginTag($tag) . $content . CHTML::endTag($tag);
	//						break;
	//					case "p":
	//						$attrs = arr::include_exclude($attrs, array("align"));
	//						$result = CHTML::beginTag($tag, $attrs) . $content . CHTML::endTag($tag);
	//						break;
	//					case "font":
	//						$attrs = arr::include_exclude($attrs, array("color", "style"));
	//						$result = CHTML::beginTag($tag, $attrs) . $content . CHTML::endTag($tag);
	//						break;
	//				}
	//			}
	//		}
	//		return $this->_convertSingles($string);
	//	}
}
