<?php

require_once dirname(dirname(__FILE__)) . '/server.application.inc';

$application = Application_Server::instance();

$application->main();
