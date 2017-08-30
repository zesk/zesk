#!/bin/bash 
cannon_opts="--verbose"

#zesk cannon  --also-match 'extends Object'  'log::' '$this->application->logger->'
zesk cannon $cannon_opts cdn_css css
zesk cannon $cannon_opts cdn_javascript javascript

echo 'Controller::factory is deprecated'
php-find.sh 'Controller::factory'
pause
