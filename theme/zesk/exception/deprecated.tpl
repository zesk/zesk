<?php

if (false) {
	/* @var $content Exception_Deprecated */
	$content = $this->content;
}	
echo html::tag("h1", "Deprecated function called: ");

echo "<pre>" . $content->getTraceAsString() . "</pre>";
