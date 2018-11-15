<?php
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Timer_Test extends Test_Unit {
    public function test_now() {
        Timer::now();
    }

    public function test_basics() {
        $initTime = false;
        $offset = 0;
        $x = new Timer($initTime, $offset);
        
        Timer::now();
        
        $x->stop();
        
        $x->mark();
        
        $x->elapsed();
        
        $comment = '';
        $x->output($comment);
        
        $comment = '';
        $x->dump($comment);
    }
}
