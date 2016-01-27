<?php
class test_syslog extends Test_Unit {

	function __construct() {
		Module::load('syslog');
		$this->assert_module('syslog');
		parent::__construct();
	}

	function test_alert() {
		$message = null;
		$host = null;
		$facility = null;
		syslog::alert($message, $host, $facility);
	}

	function test_critical() {
		$message = null;
		$host = null;
		$facility = null;
		syslog::critical($message, $host, $facility);
	}

	function test_debug() {
		$message = null;
		$host = null;
		$facility = null;
		syslog::debug($message, $host, $facility);
	}

	function test_emergency() {
		$message = null;
		$host = null;
		$facility = null;
		syslog::emergency($message, $host, $facility);
	}

	function test_error() {
		$message = null;
		$host = null;
		$facility = null;
		syslog::error($message, $host, $facility);
	}

	function test_info() {
		$message = null;
		$host = null;
		$facility = null;
		syslog::info($message, $host, $facility);
	}

	function test_notice() {
		$message = null;
		$host = null;
		$facility = null;
		syslog::notice($message, $host, $facility);
	}

	function test_send() {
		$message = null;
		$host = null;
		$facility = null;
		$severity = 3;
		syslog::send($message, $host, $facility, $severity);
	}

	function test_warning() {
		$message = null;
		$host = null;
		$facility = null;
		syslog::warning($message, $host, $facility);
	}
}

