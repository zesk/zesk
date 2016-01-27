<?php
$this->response->cdn_css('/share/zesk/css/webfonts.css', array('share' => true));
$fonts = array(
	"Arial, Arial, Helvetica, sans-serif", 
	"Arial Black, Arial Black, Gadget, sans-serif", 
	"Comic Sans MS, Comic Sans MS5, cursive", 
	"Courier New, Courier New, monospace", 
	"Georgia1, Georgia, serif", 
	"Impact, Impact5, Charcoal6, sans-serif", 
	"Lucida Console, Monaco5, monospace", 
	"Lucida Sans Unicode, Lucida Grande, sans-serif", 
	"Palatino Linotype, Book Antiqua3, Palatino, serif", 
	"Tahoma, Geneva, sans-serif", 
	"Times New Roman, Times New Roman, Times, serif", 
	"Trebuchet MS1, Trebuchet MS, sans-serif", 
	"Verdana, Verdana, Geneva, sans-serif", 
	"Symbol, Symbol (Symbol2, Symbol2)", 
	"Webdings, Webdings (Webdings2, Webdings2)", 
	"Wingdings, Zapf Dingbats (Wingdings2, Zapf Dingbats2)", 
	"MS Sans Serif4, Geneva, sans-serif", 
	"MS Serif4, New York6, serif"
);
foreach ($fonts as $font) {
	$font = arr::trim(to_list($font, array(), ","));
	foreach ($font as $index => $face) {
		if (strpos($face, " ") !== false) {
			$font[$index] = "\"$face\"";
		}
	}
	$attrs = array();
	$attrs['style'] = "font-family: " . implode(",", $font);
	if ($this->has('font_size')) {
		$attrs['style'] .= "; font-size: $this->font_size";
	}
	$attrs['class'] = "webfont-content";
	echo html::tag('div', '.webfont-sample', html::tag("div", $attrs, $this->content) . html::tag('div', '.font', implode(", ", $font)));
}
