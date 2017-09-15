<?php
namespace zesk;

$content = $this->content instanceof Timestamp ? $this->content : new Timestamp($this->content);

$now = Timestamp::now();

$strings = array(
	'year' => 'Last seen {YYYY}',
	'month' => 'Last seen {MMMM} {YYYY}',
	'week' => 'Last seen {n} {units} ago',
	'day' => 'Last seen {n} {units} ago',
	'hour' => 'Visited today, {n} {units} ago',
	'minute' => 'Visited recently, {n} {units} ago'
);
foreach ($strings as $unit => $format) {
	if (($n = $now->difference($content, $unit)) > 0) {
		$map['n'] = $n;
		$map['units'] = Locale::plural($unit, $n);
		$format = __($format, $map);
		echo $content->format($format);
		return;
	}
}
echo HTML::span(".currently-online", __('Currently online'));
