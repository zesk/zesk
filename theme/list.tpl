<?php

namespace zesk;

$content = $this->content;
if (is_array($content)) {
	$result = array();
	if (arr::is_assoc($content)) {
		foreach ($content as $k => $sValue) {
			if (is_array($sValue)) {
				$sValue = $this->theme('list', $sValue);
			}
			$result[] = HTML::tag('dt', $k) . HTML::tag("dd", $sValue);
		}
		echo HTML::tag("dl", implode("\n", $result));
	} else {
		foreach ($content as $k => $sValue) {
			if (is_array($sValue)) {
				$sValue = $this->theme('list', $sValue);
			}
			$result[] = HTML::tag('li', $sValue);
		}
		echo HTML::tag("ul", implode("\n", $result));
	}
} else {
	echo $content;
}
