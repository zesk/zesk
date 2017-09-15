<?php
/* @var $this zesk\Template */
$width = $this->geti('width', 300);
$height = $this->geti('height', 300);

$corner_image = 'http://static.marketruler.com/share/images/gradient/border-shadow-400x400-20.png';
if ($width > 400) {
	$corner_image = 'http://static.marketruler.com/share/images/gradient/border-shadow-1000x200-20.png';
} else if ($height > 400) {
	$corner_image = 'http://static.marketruler.com/share/images/gradient/border-shadow-200x1000-20.png';
}
$width_unit = $width . 'px';
$height_unit = $height . 'px';

$thickness = $this->geti('thickness', 14);
$thickness_px = $thickness . 'px';

$close_height = 30;
$timer_height = 12;

$where = $this->get('where', 'top-right');

if (!in_array($where, array(
	'left',
	'right',
	'top',
	'bottom',
	'top-right',
	'top-left',
	'bottom-right',
	'bottom-left'
))) {
	$where = 'top-right';
}

// top-right
$padding = "0 0 $thickness_px $thickness_px";
$xpos = "right";
$ypos = "top";
$bg = "url($corner_image) no-repeat scroll left bottom";
$y_close = 0;
$x_close = 0;
$xpos_close = "right";

switch ($where) {
	case 'top':
		$padding = "0 0 $thickness_px 0";
		$xpos = "left";
		$ypos = "top";
		$width_unit = "100%";
		$bg = 'url(http://static.marketruler.com/share/images/gradient/bottom-shadow-20.png) repeat-x bottom';
		break;
	case 'bottom':
		$padding = "$thickness_px 0 0 0";
		$xpos = "left";
		$ypos = "bottom";
		$width_unit = "100%";
		$bg = 'url(http://static.marketruler.com/share/images/gradient/top-shadow-20.png) repeat-x top';
		break;
	case 'left':
		$padding = "0 0 0 $thickness_px";
		$xpos = "left";
		$ypos = "top";
		$height_unit = "100%";
		$bg = 'url(http://static.marketruler.com/share/images/gradient/right-shadow-20.png) repeat-y right';
		break;
	case 'right':
		$padding = "0 0 0 $thickness_px";
		$xpos = "right";
		$ypos = "top";
		$height_unit = "100%";
		$bg = "url(http://static.marketruler.com/share/images/gradient/left-shadow-20.png) repeat-y left";
		break;
	case 'bottom-left':
		$padding = "$thickness_px $thickness_px 0 0";
		$xpos = "left";
		$ypos = "bottom";
		$bg = "url($corner_image) no-repeat right top";
		$y_close = $height - $close_height;
		$x_close = $width - $close_height;
		break;
	case 'bottom-right':
		$padding = "$thickness_px 0 0 $thickness_px";
		$xpos = "right";
		$ypos = "bottom";
		$bg = "url($corner_image) no-repeat left top";
		$y_close = $height - $close_height;
		break;
	case 'top-left':
		$padding = "0 $thickness_px $thickness_px 0";
		$xpos = "left";
		$ypos = "top";
		$x_close = $width - $close_height;
		$bg = "url($corner_image) no-repeat right bottom";
		break;
	default :
	case 'top-right':
		break;
}
$y_timer = ($ypos === "top") ? ($y_close + $close_height) : ($y_close - $timer_height);

$this->frame = null;

$this->response->content_type('text/css');

?>#zesk-overlay-frame-<?php echo $where ?> {
	position: fixed;
	background: <?php echo $bg ?>;
	border: 0;
	padding: <?php echo $padding ?>;
	<?php echo $xpos ?>: 0;
	<?php echo $ypos ?>: 0;
	z-index: 100000000;
	height: <?php echo $height_unit ?>;
	width: <?php echo $width_unit ?>;
	overflow: hidden;
	overflow-y: hidden;
}
#zesk-overlay-frame-<?php echo $where ?>-close a {
	position: fixed;
	background: url(http://static.marketruler.com/share/images/x/x-20.png);
	width: <?php echo $close_height ?>px;
	height: <?php echo $close_height ?>px;
	<?php echo $xpos_close ?>: <?php echo $x_close ?>px;
	<?php echo $ypos ?>: <?php echo $y_close ?>px;
	z-index: 100000001;
}
#zesk-overlay-frame-<?php echo $where ?>-close a:hover {
	background: url(http://static.marketruler.com/share/images/x/x-20-down.png);
}
#zesk-overlay-frame-<?php echo $where ?>-timer {
	position: fixed;
	width: <?php echo $close_height ?>px;
	height: 15px;
	font-family: Arial, sans;
	<?php echo $xpos_close ?>: <?php echo $x_close ?>px;
	<?php echo $ypos ?>: <?php echo $y_timer ?>px;
	z-index: 100000002;
	font-size: 10px !important;
	font-weight: bold;
	text-align: center;
}
