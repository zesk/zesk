<?php declare(strict_types=1);
/**
 * @version $URL: https://code.marketacumen.com/zesk/trunk/modules/inplace/theme/zesk/control/list/inplace/footer.tpl $
 * @author $Author: kent $
 * @package {package}
 * @subpackage {subpackage}
 * @copyright Copyright (C) 2016, {company}. All rights reserved.
 */
namespace zesk;

/**
 *
 */
?>

<div class="inplace-list-footer"><?php
if ($this->current_user->can($this->list_class . '::edit')) {
	echo __('Click any name in the list of {list_object_names} above to edit. Changes are saved immediately.', $this->variables);
}
?></div><?php
