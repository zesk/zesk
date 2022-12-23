<?php declare(strict_types=1);
namespace zesk;

$content = $this->content;
if (can_iterate($content)) {
	$result = [];
	$item_theme = $this->get('theme', 'list');
	$force_assoc = $this->getBool('force_assoc');
	$force_list = $this->getBool('force_list');
	if (!$force_assoc && !$force_list) {
		$wrap_tag = ArrayTools::isAssoc($content) ? 'dl' : 'ul';
	} elseif ($force_list) {
		$wrap_tag = 'ul';
	} else {
		$wrap_tag = 'dl';
	}
	$skip_empty = $this->getBool('skip_empty', true);
	if ($wrap_tag === 'dl') {
		foreach ($content as $k => $sValue) {
			if (is_array($sValue)) {
				$sValue = $this->theme($item_theme, $sValue);
			}
			if ($skip_empty && empty($sValue)) {
				continue;
			}
			$result[] = HTML::tag('dt', $k) . HTML::tag('dd', $sValue);
		}
	} else {
		foreach ($content as $k => $sValue) {
			if (is_array($sValue)) {
				$sValue = $this->theme($item_theme, $sValue);
			}
			if ($skip_empty && empty($sValue)) {
				continue;
			}
			$result[] = HTML::tag('li', $sValue);
		}
	}
	echo HTML::tag($wrap_tag, implode("\n", $result));
} else {
	echo $content;
}
