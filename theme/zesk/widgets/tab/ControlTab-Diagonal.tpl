<?php
/**
 * $URL$
 */

html::cdn_css("/share/zesk/widgets/tab/diagonal/tabs-diagonal.css");

?><div class="tabs-diagonal"><img
class="screen" alt="" src="<?php echo cdn::url("/share/zesk/widgets/tab/diagonal/tabo.gif") ?>" width="20" height="20" border="0" /><?
foreach ($this->tabs as $tab) {
	if ($tab['selected']) {
		?><img class="screen" src="<?php echo cdn::url("/share/zesk/widgets/tab/diagonal/" . ($tab['first'] ? 'tab1l.gif' : 'tab1lm.gif')) ?>" width="20" height="20" border="0" alt="" /><div
class="tab1"><span class="a"><?php echo $tab['text'] ?></span></div><?
	} else {
		?><img
class="screen" alt="" src="<?php echo cdn::url("/share/zesk/widgets/tab/diagonal/" . ($tab['last_selected'] ? 'tab1rm.gif' : ($tab['first'] ? 'tab0l.gif' : 'tab0rn.gif'))) ?>" width="20" height="20" border="0" /><div
class="tab0" onclick="document.location='<?php echo $tab['href'] ?>'"><a
href="<?php echo $tab['href'] ?>"><?php echo $tab['text'] ?></a></div><? } ?><?
} ?><img
class="screen" src="<?php echo cdn::url("/share/zesk/widgets/tab/diagonal/" . ($tab['selected'] ? 'tab1r.gif' : 'tab0r.gif')) ?>" alt=""
width="20" height="20" border="0" /><? if (!empty($this->Extra)) { ?><div class="extras"><?php echo $this->Extra ?></div><? } ?></div>
