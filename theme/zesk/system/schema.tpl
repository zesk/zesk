<?php

newline("\n");

$application = Application::instance();
$db = $application->database_factory();

$results = $application->schema_synchronize($db);
echo html::tag('ul', html::tags('li', $results));

$this->response->content_type = "text/plain";