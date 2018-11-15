<?php
namespace zesk;

class Control_Hidden_Model extends Control_Hidden {
    public function load() {
        $x = $this->request->get($this->name());

        try {
            $object = $this->model_factory($this->class, $x)->fetch();
            $this->value($object);
        } catch (\Exception $e) {
            $this->application->hooks->call("exception", $e);
        }
    }
}
