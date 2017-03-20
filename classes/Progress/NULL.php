<?php
namespace zesk;

class Progress_NULL implements Interface_Progress {
	function progress($status = null, $percent = null) {
		// No-op
	}
	function progress_push($name) {
		// No-op
	}
	function progress_pop() {
		// No-op
	}
}