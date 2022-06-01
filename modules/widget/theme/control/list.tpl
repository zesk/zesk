<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

$theme_options = [
	'first' => true,
];
echo $this->theme($this->theme_prefix, [], $theme_options);
echo HTML::tag_open($this->list_tag, HTML::addClass($this->list_attributes, PHP::parseClass($this->class)));
echo $this->theme($this->theme_header, [], $theme_options);
echo $this->theme($this->theme_content, [], $theme_options);
echo $this->theme($this->theme_footer, [], $theme_options);
echo HTML::tag_close($this->list_tag);
echo $this->theme($this->theme_suffix, [], $theme_options);
