<?php
/**
 * 
 */
namespace zesk;

$theme_options = array(
	'first' => true
);
echo $this->theme($this->theme_prefix, array(), $theme_options);
echo HTML::tag_open($this->list_tag, HTML::add_class($this->list_attributes, PHP::parse_class($this->class)));
echo $this->theme($this->theme_header, array(), $theme_options);
echo $this->theme($this->theme_content, array(), $theme_options);
echo $this->theme($this->theme_footer, array(), $theme_options);
echo HTML::tag_close($this->list_tag);
echo $this->theme($this->theme_suffix, array(), $theme_options);
