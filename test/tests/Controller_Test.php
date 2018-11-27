<?php
namespace zesk;

class Controller_Test extends Test_Unit {
    /**
     *
     */
    public function test_before_after() {
        $app = $this->application;
        $options = array();
        $route = null;
        $response = null;
        $testx = new Controller($app, $route, $response, $options);

        $testx->before();

        $testx->after();
    }
}
