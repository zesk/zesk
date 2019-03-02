<?php
/**
 * @package zesk
 * @subpackage webapp
 * @author kent
 * @copyright &copy; 2019 Market Acumen, Inc.
 */
namespace zesk\WebApp;

abstract class Generator {
	/**
	 *
	 * @param Host $host
	 * @return string
	 */
	abstract function render(Host $host);
}