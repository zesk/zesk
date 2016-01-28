<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/control/richtext.inc $
 * @package zesk
 * @subpackage default
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2006, Market Acumen, Inc.
 * 
 * TODO KMD - Fix this to integrate with modules for plug-in Richtext Editors
 */

/**
 *
 * @package zesk
 * @subpackage control
 */
class Control_RichText extends Control_Text {
	protected $theme = "control/richtext";
	public final function dimensions() {
		$rows = $this->option_integer ( "rows", 5 );
		$cols = $this->option_integer ( "cols", 80 );
		
		$maxRows = zesk::geti ( "Control_RichText::rows_max", 64 );
		$maxCols = zesk::geti ( "Control_RichText::cols_max", 200 );
		
		if ($rows > $maxRows) {
			$rows = $maxRows;
		}
		if ($cols > $maxCols) {
			$cols = $maxCols;
		}
		return array (
				$rows,
				$cols 
		);
	}
	private function clean($x) {
		$x = html::clean_tags_without_attributes ( "a", $x );
		// echo "<h1>---original-------</h1>\n";
		// dump($x);
		$x = self::entities_clean ( $x );
		// echo "<h1>---entities_clean-------</h1>\n";
		// dump($x);
		$x = html::clean_tags_attributes ( $x, "style;href;target", false );
		
		$x = html::clean_style_attributes ( $x, "font-weight;font-style;margin-left;text-align", false );
		
		// echo "<h1>---html::clean_tags_attributes-------</h1>\n";
		// dump($x);
		
		$allowed_tags = $this->option_list ( "allowed_tags", "strong;em;ul;li;h1;h2;h3;h4;h5;pre;a;blockquote;p;br;span;div" );
		$x = html::clean_tags ( $x, $allowed_tags );
		// echo "<h1>---html::clean_tags-------</h1>\n";
		// dump($x);
		
		$convert_tags = array (
				"<br>" => "<br />" 
		);
		$x = replace_map ( $convert_tags, $x );
		// echo "<h1>---replace_map-------</h1>\n";
		// dump($x);
		
		$x = html::trim_white_space ( $x );
		
		return $x;
	}
	private function autoLink($html) {
		$x = html::remove_tags ( explode ( ";", "a;img;link;style;pre" ), $html, true );
		
		$urls = false;
		if (preg_match_all ( '/[^"]([a-zA-Z]+[:\/\/]+[A-Za-z0-9\-_]+\\.+[A-Za-z0-9\.\/%&=\?\-_]+)/i', $x, $urls )) {
			foreach ( $urls as $url ) {
				$html = str_replace ( $url [1], html::a ( $url [1], $url [1] ), $html );
			}
		}
		return $html;
	}
	private static function entities_clean($x) {
		$ents = array (
				chr ( 160 ) => "&nbsp;",
				chr ( 161 ) => "&iexcl;",
				chr ( 162 ) => "&cent;",
				chr ( 163 ) => "&pound;",
				chr ( 164 ) => "&curren;",
				chr ( 165 ) => "&yen;",
				chr ( 166 ) => "&brvbar;",
				chr ( 167 ) => "&sect;",
				chr ( 168 ) => "&uml;",
				chr ( 169 ) => "&copy;",
				chr ( 170 ) => "&ordf;",
				chr ( 171 ) => "&laquo;",
				chr ( 172 ) => "&not;",
				chr ( 173 ) => "&shy;",
				chr ( 174 ) => "&reg;",
				chr ( 175 ) => "&macr;",
				chr ( 176 ) => "&deg;",
				chr ( 177 ) => "&plusmn;",
				chr ( 178 ) => "&sup2;",
				chr ( 179 ) => "&sup3;",
				chr ( 180 ) => "&acute;",
				chr ( 181 ) => "&micro;",
				chr ( 182 ) => "&para;",
				chr ( 183 ) => "&middot;",
				chr ( 184 ) => "&cedil;",
				chr ( 185 ) => "&sup1;",
				chr ( 186 ) => "&ordm;",
				chr ( 187 ) => "&raquo;",
				chr ( 188 ) => "&frac14;",
				chr ( 189 ) => "&frac12;",
				chr ( 190 ) => "&frac34;",
				chr ( 191 ) => "&iquest;",
				chr ( 192 ) => "&Agrave;",
				chr ( 193 ) => "&Aacute;",
				chr ( 194 ) => "&Acirc;",
				chr ( 195 ) => "&Atilde;",
				chr ( 196 ) => "&Auml;",
				chr ( 197 ) => "&Aring;",
				chr ( 198 ) => "&AElig;",
				chr ( 199 ) => "&Ccedil;",
				chr ( 200 ) => "&Egrave;",
				chr ( 201 ) => "&Eacute;",
				chr ( 202 ) => "&Ecirc;",
				chr ( 203 ) => "&Euml;",
				chr ( 204 ) => "&Igrave;",
				chr ( 205 ) => "&Iacute;",
				chr ( 206 ) => "&Icirc;",
				chr ( 207 ) => "&Iuml;",
				chr ( 208 ) => "&ETH;",
				chr ( 209 ) => "&Ntilde;",
				chr ( 210 ) => "&Ograve;",
				chr ( 211 ) => "&Oacute;",
				chr ( 212 ) => "&Ocirc;",
				chr ( 213 ) => "&Otilde;",
				chr ( 214 ) => "&Ouml;",
				chr ( 215 ) => "&times;",
				chr ( 216 ) => "&Oslash;",
				chr ( 217 ) => "&Ugrave;",
				chr ( 218 ) => "&Uacute;",
				chr ( 219 ) => "&Ucirc;",
				chr ( 220 ) => "&Uuml;",
				chr ( 221 ) => "&Yacute;",
				chr ( 222 ) => "&THORN;",
				chr ( 223 ) => "&szlig;",
				chr ( 224 ) => "&agrave;",
				chr ( 225 ) => "&aacute;",
				chr ( 226 ) => "&acirc;",
				chr ( 227 ) => "&atilde;",
				chr ( 228 ) => "&auml;",
				chr ( 229 ) => "&aring;",
				chr ( 230 ) => "&aelig;",
				chr ( 231 ) => "&ccedil;",
				chr ( 232 ) => "&egrave;",
				chr ( 233 ) => "&eacute;",
				chr ( 234 ) => "&ecirc;",
				chr ( 235 ) => "&euml;",
				chr ( 236 ) => "&igrave;",
				chr ( 237 ) => "&iacute;",
				chr ( 238 ) => "&icirc;",
				chr ( 239 ) => "&iuml;",
				chr ( 240 ) => "&eth;",
				chr ( 241 ) => "&ntilde;",
				chr ( 242 ) => "&ograve;",
				chr ( 243 ) => "&oacute;",
				chr ( 244 ) => "&ocirc;",
				chr ( 245 ) => "&otilde;",
				chr ( 246 ) => "&ouml;",
				chr ( 247 ) => "&divide;",
				chr ( 248 ) => "&oslash;",
				chr ( 249 ) => "&ugrave;",
				chr ( 250 ) => "&uacute;",
				chr ( 251 ) => "&ucirc;",
				chr ( 252 ) => "&uuml;",
				chr ( 253 ) => "&yacute;",
				chr ( 254 ) => "&thorn;",
				chr ( 255 ) => "&yuml;" 
		);
		$x = str_replace ( array_keys ( $ents ), array_values ( $ents ), $x );
		$x = str_replace ( "(TM)", "&#0153;", $x );
		$x = str_replace ( "(C)", "&copy;", $x );
		return $x;
	}
	
	/*
	 * RTESafe: From Cross-Browser Rich Text Editor http://www.kevinroth.com/rte/demo.htm
	 */
	static private function RTESafe($strText) {
		// returns safe code for preloading in the RTE
		$tmpString = trim ( $strText );
		
		// convert all types of single quotes
		$tmpString = str_replace ( chr ( 145 ), chr ( 39 ), $tmpString );
		$tmpString = str_replace ( chr ( 146 ), chr ( 39 ), $tmpString );
		$tmpString = str_replace ( "'", "&#39;", $tmpString );
		
		// convert all types of double quotes
		$tmpString = str_replace ( chr ( 147 ), chr ( 34 ), $tmpString );
		$tmpString = str_replace ( chr ( 148 ), chr ( 34 ), $tmpString );
		
		// replace carriage returns & line feeds
		$tmpString = str_replace ( chr ( 10 ), " ", $tmpString );
		$tmpString = str_replace ( chr ( 13 ), " ", $tmpString );
		
		$tmpString = str_replace ( "<", "&lt;", $tmpString );
		$tmpString = str_replace ( ">", "&gt;", $tmpString );
		$tmpString = str_replace ( "\n", "\\n", $tmpString );
		
		return $tmpString;
	}
}
