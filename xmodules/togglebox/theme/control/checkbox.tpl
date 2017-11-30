<?php
/**
 * 
 */
namespace zesk;

/**
 * 
 */
echo $this->theme(ZESK_ROOT . 'theme/zesk/control/checkbox', array(
	"input_prefix" => HTML::tag_open("div", ".togglebox"),
	"input_suffix" => HTML::tag_close("div")
));
