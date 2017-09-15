<?php
class Module_bxSlider extends zesk\Module_JSLib {
	protected $css_paths = array(
		'/share/bxslider/jquery.bxslider.css'
	);
	protected $javascript_paths = array(
		'/share/bxslider/jquery.bxslider.js'
	);
	protected $javascript_settings = array();
	protected $jquery_ready = array(
		'$(".bxslider").bxSlider(zesk.get_path("modules.bxslider", {}));'
	);
	
	// See http://bxslider.com/options
	protected $javascript_settings_inherit = array(
		'mode' => 'horizontal',
		'speed' => 500,
		'slideMargin' => 0,
		'startSlide' => 0,
		'randomStart' => false,
		'slideSelector' => '',
		'infiniteLoop' => true,
		'hideControlOnEnd' => false,
		'easing' => null,
		'captions' => false,
		'ticker' => false,
		'tickerHover' => false,
		'adaptiveHeight' => false,
		'adaptiveHeightSpeed' => 500,
		'video' => false,
		'responsive' => true,
		'useCSS' => true,
		'preloadImages' => 'visible',
		'touchEnabled' => true,
		'swipeThreshold' => true,
		'oneToOneTouch' => true,
		'preventDefaultSwipeX' => true,
		'preventDefaultSwipeY' => false,
		'pager' => true,
		'pagerType' => 'full',
		'pagerShortSeparator' => ' / ',
		'pagerSelector' => '',
		'pagerCustom' => null,
		'controls' => true,
		'nextText' => 'Next',
		'prevText' => 'Prev',
		'nextSelector' => '',
		'prevSelector' => '',
		'autoControls' => false,
		'startText' => 'Start',
		'stopText' => 'Stop',
		'autoControlsCombine' => false,
		'autoControlsSelector' => null,
		'auto' => false,
		'pause' => 4000,
		'autoStart' => true,
		'autoDirection' => 'next',
		'autoHover' => false,
		'autoDelay' => 0,
		'minSlides' => 1,
		'maxSlides' => 1,
		'moveSlides' => 0,
		'slideWidth' => 0
	);
}
