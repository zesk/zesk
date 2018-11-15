<?php
namespace zesk;

class Controller_Job extends Controller {
    public function action_monitor(Job $job) {
        $result = array(
            'id' => $job->id(),
            'message' => $job->status,
        );
        $progress = $job->progress;
        if ($progress) {
            $result['progress'] = floatval($progress);
        }
        if (!$job->completed) {
            $result['wait'] = $job->refresh_interval();
        } else {
            $result['progress'] = 100;
            $result['completed'] = $job->completed;
            if ($job->has_data("content")) {
                $result['content'] = $job->data("content");
            }
        }
        $this->json($result);
    }
}
