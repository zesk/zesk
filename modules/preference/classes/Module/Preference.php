<?php
namespace zesk;

class Module_Preference extends Module {
    protected $model_classes = array(
        "zesk\\Preference",
        "zesk\\Preference_Type",
    );
}
