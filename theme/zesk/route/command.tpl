<?php

$response = $this->response;

if ($response->content_type() === "text/html") {
	echo HTML::tag("pre", implode("\n", $this->content));	
} else {
	echo arr::join_wrap($this->content, "    ", "\n");
}
