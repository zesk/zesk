<?php
namespace zesk;

/* @var $this Template */
/* @var $locale Locale */
/* @var $application Application */
/* @var $session Session */
/* @var $request Request */
/* @var $response Response */
/* @var $current_user User */

/* @var $object Feed */
$object = $this->object;

/* @var $data Interface_Data */
$data = $this->data;
$feed_update_frequency = $this->geti('feed_update_frequency', 600);
$limit = $this->geti("limit", null);
if ($limit <= 0) {
	$application->logger->warning("Limit passed to {file} is <= 0 ({limit}), assuming no limit", array(
		"file" => __FILE__,
		"limit" => $limit,
	));
	$limit = null;
}

$url = $object->url();
$prefix = $this->get('name', md5($url)) . "_";

if ($data instanceof Interface_Data) {
	$content = $data->data($prefix . 'content');
	$updated = $data->data($prefix . 'updated');
} else {
	$content = $updated = null;
}
$now = Timestamp::now();

if ($content && $updated instanceof Timestamp) {
	$expires = $updated->add_unit($feed_update_frequency, Timestamp::UNIT_SECOND);
	if ($expires->before($now)) {
		$data->delete_data(array(
			$prefix . 'content',
		));
	} else {
		echo $content;
		return;
	}
}

/* @var $attempted Timestamp */
$attempted = $data->data($prefix . 'attempted');
if ($attempted instanceof Timestamp && $attempted->add_unit(60, Timestamp::UNIT_SECOND)->after($now)) {
	$application->logger->warning("Only attempt download once a minute - waiting {n_seconds} {seconds}", array(
		"n_seconds" => $n_seconds = $attempted->difference($now),
		"seconds" => $locale->plural($locale->__("second"), $n_seconds),
	));
} else {
	if ($object->execute()) {
		ob_start();
		$items = array();
		foreach ($object as $post) {
			/* @var $post Feed_Post */
			$items[] = $post->theme("item");
			if ($limit !== null && count($items) >= $limit) {
				break;
			}
		}
		echo HTML::ediv('.feed-view', implode("\n", $items));
		echo HTML::div(".last-updated", __("Last updated {when}", array(
			"when" => $updated->format($locale, "{delta}"),
		)));
		$content = ob_get_clean();
		$data->data($prefix . "content", $content);
		$data->data($prefix . "updated", $now);
		$data->delete_data($prefix . "attempted");
		echo $content;
		return;
	}
}

$application->logger->error($object->errors());
if ($content) {
	$application->logger->warning("Feed {name} Using cached content - unable to update feed", compact("name"));
	echo $content;
}
$data->data($prefix . "attempted", $now);
