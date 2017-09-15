<?php
namespace zesk;

echo $this->theme("exception", array(
	"suffix" => HTML::tag("pre", _dump(zesk()->autoloader->path()))
));