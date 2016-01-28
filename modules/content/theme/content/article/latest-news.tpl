<?php
/**
* $URL$
* @package zesk
* @subpackage default
* @author Kent Davidson <kent@marketacumen.com>
* @copyright Copyright &copy; 2006, Market Acumen, Inc.
*/

$aa = $this->Articles;
$link = $this->Link;

?><dl class="article-list"><?php
foreach ($aa as $a) {
	if ($a->member_is_empty("Body")) {
		?><dt><?php echo  $a->Name ?></dt><dd><?php echo  $a->summary() ?></dd><?php
	} else {
		$href = url::query_format($link,array("ID" => $a->id()));
		?><dt><a href="<?php echo  $href ?>"><?php echo  $a->Name ?></a></dt>
		<dd><?php echo  $a->summary() ?> ... <?php echo  html::a($href, $this->ReadMore) ?></dd><?php
	}
}
if (count($aa) === 0) {
	echo $this->option('empty_string', "No current news.");
}
?></dl>
