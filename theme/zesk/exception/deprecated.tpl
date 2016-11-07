<?php

if (false) {
	/* @var $content Exception_Deprecated */
	$content = $this->content;
}	
echo HTML::tag("h1", "Deprecated function called: ");

echo "<pre>" . $content->getTraceAsString() . "</pre>";
