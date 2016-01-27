<?php

$content = $this->content;
if (is_array($content)) {
	$result = array();
	if (arr::is_assoc($content)) {
		foreach ($content as $k => $sValue) {
			if (is_array($sValue)) {
				$sValue = $this->theme('list', $sValue);
			}
			$result[] = html::tag('dt', $k) . html::tag("dd", $sValue);
		}
		echo html::tag("dl", implode("\n", $result));
	} else {
		foreach ($content as $k => $sValue) {
			if (is_array($sValue)) {
				$sValue = $this->theme('list', $sValue);
			}
			$result[] = html::tag('li', $sValue);
		}
		echo html::tag("ul", implode("\n", $result));
	}
} else {
	echo $content;
}
