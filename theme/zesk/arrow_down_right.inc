<?php
/**
 * @version $URL: https://code.marketacumen.com/zesk/trunk/theme/zesk/arrow_down_right.inc $
 * @package zesk
 * @subpackage theme
 * @author $Author: kent $
 * @copyright Copyright &copy; 2011, Market Acumen, Inc.
 */

/* @var $this Template */
$suffix = "";
//$suffix = ($this->jquery) ? "_jquery" : "";
$this->state_url = htmlspecialchars($this->state_url);

?><div class="toggle-arrow"><a class="toggle-arrow"
href="javascript:arrow_down_right<?php echo $suffix ?>('<?php echo $this->id_class ?>','<?php echo $this->state_url ?>')"><img alt="" src="<?php echo cdn::url("/share/images/toggle/small-" . ($this->is_open ? "down" : "right") . ".gif") ?>" id="<?php echo $this->id_class ?>_img"
/></a><?php echo "<$this->label_tag>" ?><a <? if (isset($this->class)) { ?>class="<?php echo $this->class ?>" <? } ?>href="javascript:arrow_down_right<?php echo $suffix ?>('<?php echo $this->id_class ?>','<?php echo $this->state_url ?>')"><?php echo $this->title ?></a><?php echo "</$this->label_tag>" ?></div>
