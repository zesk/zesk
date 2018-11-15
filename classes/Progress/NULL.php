<?php
namespace zesk;

class Progress_NULL implements Interface_Progress {
    public function progress($status = null, $percent = null) {
        // No-op
    }

    public function progress_push($name) {
        // No-op
    }

    public function progress_pop() {
        // No-op
    }
}
