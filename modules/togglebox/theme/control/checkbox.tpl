<?php
echo $this->theme(ZESK_ROOT . 'theme/zesk/control/checkbox', array(
	"input_prefix" => html::tag_open("div", ".togglebox"),
	"input_suffix" => html::tag_close("div")
));