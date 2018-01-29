<?php
/**
 * @version $URL: https://code.marketacumen.com/zesk/trunk/theme/body/centered.tpl $
 * @author $Author: kent $
 * @package {package}
 * @subpackage {subpackage}
 * @copyright Copyright (C) 2016, {company}. All rights reserved.
 */
namespace zesk;

/* @var $this \zesk\Template */
/* @var $locale \zesk\Locale */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */
/* @var $current_user \User */
ob_start();
?>
<style type="text/css">
#wrapper {
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	position: fixed;
	display: table;
	color: gray;
}

#inner-wrap {
	display: table-cell;
	vertical-align: middle;
	text-align: center;
}

body {
	font-size: 15px;
}

h1 {
	color: black;
	font-size: 32px;
}

h2 {
	font-weight: normal;
	font-size: 20px;
}

hr {
	margin-top: 40px;
	margin-bottom: 30px;
	width: 60%;
	color: #444444;
	background-color: #444444;
	height: 1px;
	border: 0;
}

ol {
	text-align: left;
	width: 400px;
	margin: 0px auto;
}

li {
	margin-bottom: 5px;
}
</style>
<?php
$response->html()->css_inline(HTML::extract_tag_contents("style", ob_get_clean()));
?>
<div id="wrapper">
	<div id="inner-wrap">
			<?php
			echo $this->content;
			?>
		</div>
</div>
