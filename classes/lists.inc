<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/lists.inc $
 * @package zesk
 * @subpackage system
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
zesk()->deprecated();

/**
 * Handles more traditional Cold-Fusion style lists, basically a string separated by characters
 * Lists, by default, are separated by semicolon, ";" but can be separated by any character sequence
 * These are meant to handle simplistic cases, and does not really scale for lists of thousands of items.
 * If that's the case, then use arrays or another structure.
 * 
 * All functions within support array lists as well; the semantic is:
 * - if an array is passed in for a list, then an array is returned
 * - if a delimited-string list is passed in for a list, then a delimited-string list is returned
 * 
 * @deprecated 2016-09
 * @author kent
 */
class lists extends zesk\Lists {}