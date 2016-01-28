<?php

/**
 * temporal 1 |ˈtemp(ə)rəl|
 * adjective
 * 1. relating to worldly as opposed to spiritual affairs; secular.
 * 2. of or relating to time.
 *  - Grammar relating to or denoting time or tense.
 * @author kent
 */
abstract class Temporal {
	abstract function format($format_string = null, $locale = null);
	abstract function formatting($locale = null);
}