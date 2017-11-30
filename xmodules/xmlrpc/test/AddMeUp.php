<?php
namespace zesk;

class AddMeUp extends \xmlrpc\Server {
	function rpc_add($a, $b) {
		return $a + $b;
	}
}
