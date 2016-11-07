<?php

echo HTML::a('mailto:' . $this->content, array(
	'class' => $this->class,
	'id' => $this->id
), $this->get('text', $this->content));
