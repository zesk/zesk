<?php
/**
 *
 */
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;
use zesk\HTML;
use zesk\Module;
use zesk\Exception_Class_NotFound;
use zesk\Exception_Semantics;

/**
 *
 * @author kent
 *
 */
class Module_CSS_Inline extends zesk\Module {
	public function initialize() {
		if ($this->application->autoloader->load("TijsVerkoyen\CssToInlineStyles\CssToInlineStyles", true)) {
			throw new Exception_Class_NotFound("TijsVerkoyen\CssToInlineStyles\CssToInlineStyles");
		}
	}
	public function process_html($content, $css = null) {
		// create instance
		$cssToInlineStyles = new CssToInlineStyles();
		
		$html = $content;
		if ($css === null) {
			$css_tags = HTML::extract_tags("style", $content);
			if (!$css_tags) {
				throw new Exception_Semantics("No style tags found in HTML content to apply");
			}
			$html = HTML::remove_tags("style", $content);
			$css = "";
			foreach ($css_tags as $tag) {
				$css .= $tag->inner_html() . "\n";
			}
		}
		
		// output
		return $cssToInlineStyles->convert($html, $css);
	}
}

