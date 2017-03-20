<?php
namespace zesk;

echo $this->prefix;
echo $this->theme($this->theme_prefix);
echo HTML::tag_open($this->filter_tag, HTML::add_class($this->filter_attributes, $this->class));
echo $this->theme($this->theme_header);
echo $this->theme("zesk/control/widgets");
echo $this->theme($this->theme_footer);
echo HTML::tag_close($this->filter_tag);
echo $this->theme($this->theme_suffix);
echo $this->suffix;
