<?php
/**
 * $URL$
 * @package zesk
 * @subpackage default
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2006, Market Acumen, Inc.
 */
$w = $this->Widget;
$idname = $w->option("idname","ID");
$id = $this->Object[$idname];
?><input class="tiny" disabled="disabled" type="button" name="OK[<?php echo $id ?>]" value="Save" onclick="list_dhtml_save(this,<?php echo $id ?>)" /><?
if (!empty($id)) {
	?><input type="button" class="tiny" value="Delete" src="/share/images/actions/delete.gif" width="18" height="18" onclick="list_dhtml_delete(this,<?php echo $id ?>)" /><div class="list-message" id="list-message-<?php echo $id ?>"></div><?
}
