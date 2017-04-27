<?php
namespace zesk;

/* @var $content Exception_Deprecated */
echo HTML::tag("h1", "Deprecated function called: ");

echo "<pre>" . $content->getTraceAsString() . "</pre>";
