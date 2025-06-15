#!/usr/bin/env php
<?php declare(strict_types=1);

use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider;
use testZesk\TestApplication;

/* @var $application TestApplication */

$application = require_once __DIR__ . '/../test.application.php';

$application->modules->load('Doctrine');

$commands = [];

ConsoleRunner::run(new SingleManagerProvider($application->entityManager()), $commands);
