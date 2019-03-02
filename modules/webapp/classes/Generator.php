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
	abstract public function render(Host $host);
}
