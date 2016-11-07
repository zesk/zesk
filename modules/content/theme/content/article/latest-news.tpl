<?php
/**
 * $URL$
 * @package zesk
 * @subpackage default
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2016, Market Acumen, Inc.
 */
namespace zesk;

/* @var $this \zesk\Template */
/* @var $zesk \zesk\Kernel */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response_Text_HTML */
/* @var $current_user \User */
/* @var $object \Content_Article */
$aa = $this->Articles;
$link = $this->Link;

?><dl class="article-list"><?php
foreach ($aa as $a) {
	if ($a->member_is_empty("Body")) {
		?><dt><?php echo  $a->Name ?></dt>
	<dd><?php echo  $a->summary() ?></dd><?php
	} else {
		$href = URL::query_format($link, array(
			"ID" => $a->id()
		));
		?><dt>
		<a href="<?php echo  $href ?>"><?php echo  $a->Name ?></a>
	</dt>
	<dd><?php echo  $a->summary() ?> ... <?php echo  HTML::a($href, $this->ReadMore) ?></dd><?php
	}
}
if (count($aa) === 0) {
	echo $this->option('empty_string', "No current news.");
}
?></dl>
