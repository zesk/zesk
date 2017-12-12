<?php
	
namespace zesk;

class Module_ORM extends Module {
	public $orm_classes = array(Server::class, Settings::class, Meta::class, Domain::class, Lock::class);
}
