<?php
/**
 * @version $URL$
 * @author $Author: kent $
 * @package zesk
 * @subpackage command
 * @copyright &copy; 2016 Market Acumen, Inc.
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
/* @var $current_user \zesk\User */

?>
# Zesk Command

    zesk [ --set name=value ] [ --name=value ] [ --unset name ] [ --cd dir ] [ --search dir ] [ --config file ] command0 [ command1 ... ]

Zesk command-line management interface. Execute commands within an application context.
Commands may process arguments differently.

Searches from `--search` directories, or current working directory for a `*.application.inc` file before running any commands.
Pre-set globals using `--set`, `--name=value`, `--config`.

Change starting context using `--cd` (to search elsewhere by default) or adding `--search`.

<?php
$tab = "";
foreach ($this->categories as $category => $commands) {
	echo "## $category ##\n\n";
	foreach ($commands as $command => $info) {
		echo "### `$command`\n\n";
		$desc = avalue($info, "desc", __("No description provided."));
		$parameters = array();
		foreach (avalue($info, 'global', array()) as $global => $foo) {
			$parameters[] = "- `$global` (" . $foo[0] . ")" . "\n" . Text::indent(rtrim($foo[2]), 1, false, " - ");
		}
		$parameters = implode("\n", $parameters);
		if ($parameters) {
			$parameters = "$tab$tab" . __("#### Globals:") . "\n\n" . $parameters . "\n";
		}
		$desc .= "\n\n";
		echo rtrim(Text::indent($desc, 2, true, $tab)) . "\n\n$parameters";
	}
	echo "\n";
}
