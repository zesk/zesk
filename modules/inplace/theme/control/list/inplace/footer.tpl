<div class="inplace-list-footer"><?php
if ($this->current_user->can($this->list_class . "::edit")) {
	echo __('Click any name in the list of {list_object_names} above to edit. Changes are saved immediately.', $this->variables);
}
?></div><?php
