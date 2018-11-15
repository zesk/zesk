<?php
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Server_Test extends Test_Unit {
    protected $load_modules = array(
        "ORM",
        "MySQL",
    );

    protected function initialize() {
        $this->schema_synchronize(Server::class);
    }

    public function test_Server() {
        $this->application->configuration->HOST = "localhost";
        
        $testx = new Server($this->application);
        $this->assert_instanceof($testx, Server::class);
        
        $testx = Server::singleton($this->application);
        
        $this->assert_instanceof($testx, Server::class);
        $path = "/";
        $testx->id = 1;
        $testx->update_state($path);
    }
}
