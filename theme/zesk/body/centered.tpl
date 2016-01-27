
<?php

html::css_inline();

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
html::css_inline_end();
?>
	<div id="wrapper">
		<div id="inner-wrap">
			<?php echo $this->content; ?>
		</div>
	</div>
