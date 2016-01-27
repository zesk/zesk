<?php

echo $this->prefix;
echo $this->theme($this->theme_prefix);
echo html::tag_open($this->filter_tag, html::add_class($this->filter_attributes, $this->class));
echo $this->theme($this->theme_header);
echo $this->theme("control/widgets");
echo $this->theme($this->theme_footer);
echo html::tag_close($this->filter_tag);
echo $this->theme($this->theme_suffix);
echo $this->suffix;
