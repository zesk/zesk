<?php
use zesk\Timestamp;

if (false) {
	/* @var $this zesk\Template */
	
	$application = $this->application;
	/* @var $application ZeroBot */
	
	$session = $this->session;
	/* @var $session Session */
	
	$request = $this->request;
	/* @var $request Request */
	
	$response = $this->response;
	/* @var $response zesk\Response_Text_HTML */
	
	$current_user = $this->current_user;
	/* @var $current_user User */
	
	/* @var $object zesk\Feed */
	$object = $this->object;
}

/* @var $data zesk\Interface_Data */
$data = $this->data;
$feed_update_frequency = $this->geti('feed_update_frequency', 600);
$limit = $this->geti("limit", null);
if ($limit <= 0) {
	log::warning("Limit passed to {file} is <= 0 ({limit}), assuming no limit", array(
		"file" => __FILE__,
		"limit" => $limit
	));
	$limit = null;
}

$url = $object->url();
$prefix = $this->get('name', md5($url)) . "_";

if ($data instanceof zesk\Interface_Data) {
	$content = $data->data($prefix . 'content');
	$updated = $data->data($prefix . 'updated');
} else {
	$content = $updated = null;
}
$now = Timestamp::now();

if ($content && $updated instanceof Timestamp) {
	$expires = $updated->add_unit("second", $feed_update_frequency);
	if ($expires->before($now)) {
		$data->delete_data(array(
			$prefix . 'raw_content'
		));
	} else {
		echo $content;
		return;
	}
}

/* @var $attempted Timestamp */
$attempted = $data->data($prefix . 'attempted');
if ($attempted instanceof Timestamp && $attempted->add_unit("second", 60)->after($now)) {
	log::warning("Only attempt download once a minute - waiting {n_seconds} {seconds}", array(
		"n_seconds" => $n_seconds = $attempted->difference($now),
		"seconds" => zesk\Locale::plural(__("second"), $n_seconds)
	));
} else {
	if ($object->execute()) {
		ob_start();
		$items = array();
		foreach ($object as $post) {
			/* @var $post zesk\Feed_Post */
			$items[] = $post->theme("item");
			if ($limit !== null && count($items) >= $limit) {
				break;
			}
		}
		echo HTML::ediv('.feed-view', implode("\n", $items));
		$content = ob_get_clean();
		$data->data($prefix . "content", $content);
		$data->data($prefix . "updated", $now);
		$data->delete_data($prefix . "attempted");
		echo $content;
		return;
	}
}

log::error($object->errors());
if ($content) {
	log::warning("Feed {name} Using cached content - unable to update feed", compact("name"));
	echo $content;
}
$data->data($prefix . "attempted", $now);
