<?php
class IPBan_Parser_HTTPd extends IPBan_Parser {
	final function parser() {
		return Parse_Log::factory("httpd", $this->options);
	}
}
