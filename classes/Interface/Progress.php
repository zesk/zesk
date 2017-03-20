<?php
/**
 * 
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
interface Interface_Progress {
	function progress_push($name);
	function progress_pop();
	function progress($status = null, $percent = null);
}