<?php

echo html::div('.row', html::div('.col-xs-12 action-edit', html::span(array(), __('No {list_object_names}.', array(
	"list_object_names" => $this->list_object_names
)))));
