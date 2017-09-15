<?php
/**
 * @version $URL: https://code.marketacumen.com/zesk/trunk/share/jquery/test/jquery.glow.php $
 * @author $Author: kent $
 * @copyright Copyright &copy; 2011, Market Acumen, Inc.
 */
$js_source = <<<EOF
	$(document).ready(function() {
		$('blockquote').glow();
		setTimeout(function() { $('.sub blockquote').glow('red', 7000); }, 5000);
		setTimeout(function() { $('.background-test blockquote').glow('orange'); }, 10000);
		setTimeout(function() { $('h1').glow('purple', null, 100); }, 15000);
		setTimeout(function() { $('pre').glow('rgb(0,80,0)', null, 3); }, 15000);
		$('blockquote').click(function (x) { $(x.currentTarget).glow('magenta', 500, 100); });
		$('.background-test').click(function (x) { $(x.currentTarget).glow('grey', 500, 100); });
	});
EOF;

?><html>
<head>
<title>jQuery Glow Plugin Page</title>
<style type="text/css">
div {
	display: block;
}

div.background-test {
	padding: 10px;
}

blockquote {
	display: block;
	border: 1px solid #777;
	padding: 20px;
}

.background-test {
	background-color: #000;
	color: #FFF
}

.default-bg {
	padding: 4px;
	background-color: #FF9;
}
</style>
</head>
<h1>Documentation</h1>
<pre>
$(<em>selector</em>).glow(<em>color</em>, <em>duration</em>, <em>steps</em>);
</pre>
<p>


<ul>
	<li><em>color</em> can be a color name, a hex color (#ABC or #FFEEEE),
		or rgb(12,42,43)</li>
	<li><em>duration</em> is the time in milliseconds to glow (default is
		1000, or 1 second)</li>
	<li><em>steps</em> is the number of color changes to make in the glow
		effect. Default is 50.</li>
</ul>
<p>Pass null or false for any value which you want to use the default.
	All arguments are optional.</p>
<p class="default-bg">The default color is #FFFF99, a light yellow.</p>
<h1>Why use this when effects.highlight.js does the same thing?</h1>
<p>Well, it's lighter weight than the whole UI library, and the
	$.animate interpolation doesn't seem to work correctly with non-white
	background colors.</p>
<p>So I wrote this, it was quick and dirty, and it understands all of
	the colors out there, has similar options, and is tiny.</p>
<h1>Releases</h1>
<ul>
	<li><strong>September 27th, 2009</strong>
		<ul>
			<li>Fixed issues with Opera (Thanks, Simeon Hvarchilkov)</li>
			<li>Added rgba(0,0,0,0) parsing for Safari</li>
			<li>Transparent colors rgba(0,0,0,0) are ignored now as well</li>
			<li>Fixed demo to support click</li>
			<li>Tagged glowed items to avoid glowing the same item at the same
				time</li>
		</ul></li>
	<li><strong>March 25th, 2009</strong> &mdash; Initial release</li>
</ul>
<h1>Download</h1>
<ul>
	<li><a
		href="http://static.marketruler.com/share/zesk/jquery/jquery.glow.js">Download
			jQuery Glow Plugin (Minified)</a></li>
	<li><a
		href="http://test.static.marketruler.com/share/zesk/jquery/jquery.glow.js">Download
			jQuery Glow Plugin (Uncompressed)</a></li>
</ul>
<h1>Source code</h1>
<p>Hit refresh to watch the demo again.</p>
<pre><?php echo htmlspecialchars($js_source) ?></pre>
<h1>Contact</h1>
<script type="text/javascript">
var e = unescape("Ej%29q%7BnoF+vjru%7DxCtnw%7DIvj%7Btn%7D%7B%7Eun%7B7lxv+Gtnw%7DIvj%7Btn%7D%7B%7Eun%7B7lxvE8jG");
var i,p='';for(i=0;i<e.length;i++){p+=String.fromCharCode(((e.charCodeAt(i)-41)%240)+32);}
document.write(p);
</script>
<noscript>
	<strong>You need JavaScript to view this page.</strong>
</noscript>
<h1>jQuery Glow demo</h1>
<blockquote>Here's a message you may want people to pay attention to.
	Click any of these boxes to glow magenta.</blockquote>
<h2>Div and then a blockquote</h2>
<div class="sub">
	<blockquote>Here's a second (red) message you may want people to pay
		attention to.</blockquote>
</div>
<div class="background-test">
	<h1>Reverse color tests</h1>
	<blockquote>Another color, entirely.</blockquote>
</div>
<h1>Reverse color test 2</h1>
<blockquote class="background-test">Another color, entirely.</blockquote>

<script src="../jquery.js" type="text/javascript"></script>
<script src="../jquery.glow.js" type="text/javascript"></script>
<script type="text/javascript">
<?php echo $js_source ?>
</script>
</html>
