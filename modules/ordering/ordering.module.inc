<?php

Module::register_paths();

zesk::add_hook('Object::pre_insert', 'Module_Ordering::object_pre_insert');

