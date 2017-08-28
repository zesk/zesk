<?php
namespace zesk;

class MyServer extends \xmlrpc\Server {
	function rpc_capitalize($string) {
		return str::capitalize($string);
	}
}
