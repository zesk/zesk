<?php

use zesk\HTML;

newline("\n");

$application = Application::instance();
$db = $application->database_factory();

$results = $application->schema_synchronize($db);
echo HTML::tag('ul', HTML::tags('li', $results));

$this->response->content_type = "text/plain";
