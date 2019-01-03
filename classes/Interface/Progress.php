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
	public function progress_push($name);

	public function progress_pop();

	public function progress($status = null, $percent = null);
}
