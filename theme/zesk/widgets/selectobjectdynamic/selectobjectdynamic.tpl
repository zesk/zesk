<?php
ruler_html::overlabel();
?><div class="control-select-object-dynamic" id="<?php echo $this->column ?>_widget">
	<div class="csod-select csod-none search" style="display: none"><input name="<?php echo $this->column ?>" type="hidden" value="" /></div>
	<div class="csod-input search-text csod-some">
		<div class="overlabel-pair">
			<label for="<?php echo $this->column ?>_query" class="overlabel"><?php echo $this->get("search_label", __('Search')) ?></label>
			<input type="text" size="<?php echo $this->get('show_size', 20) ?>" value="<?php echo $this->get('value') ?>" name="<?php echo $this->column ?>_query" id="<?php echo $this->column ?>_query" />
		</div>
		<img class="csod-wait csod-none" width="16" height="16" border="0" src="<?php echo cdn::url("/share/images/spinner/spinner-16x16.gif") ?>" title="Loading ..." alt="Loading ..." style="display: none;" />
	</div>
	<div class="csod-search csod-button csod-some search"
		onclick="Control_Select_Object_Dynamic_Update('<?php echo $this->column ?>','<?php echo $this->url_update ?>')"><img
		src="<?php echo cdn::url("/share/images/search/search.png") ?>" width="16" height="16" alt="<?php echo __('Search') ?>" title="<?php echo __('Search') ?>" /></div>
	<div class="csod-reset csod-button csod-some search-reset"
		onclick="Control_Select_Object_Dynamic_Reset('<?php echo $this->column ?>')"><img
		src="<?php echo cdn::url("/share/images/search/x.png") ?>" width="16" height="16" alt="<?php echo __('Reset') ?>" title="<?php echo __('Reset') ?>" /></div>
	<div class="csod-message csod-none" style="display: none;"><span class="count">0</span> results found, be more specific.</div>
	<div class="csod-message-no csod-none" style="display: none;">No matches for &quot;<span class="query"></span>&quot;, try again.</div>
</div>
