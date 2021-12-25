<?php declare(strict_types=1);
/**
 * @copyright &copy; 2016 Market Acumen, Inc.
 */
namespace zesk;

/* @var $this \zesk\Template */
/* @var $locale \zesk\Locale */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */
/* @var $current_user \zesk\User */
$aliases = to_array($this->aliases);
$aliases = ArrayTools::flip_multiple($aliases);

?># Zesk Command

    zesk [ --set name=value ] [ --name=value ] [ --unset name ] [ --cd dir ] [ --search dir ] [ --config file ] command0 [ ... command-0-options ... ] [ command1 ... ]

Zesk command-line management interface. Execute commands within an application context.
Commands may process arguments differently.

Searches from `--search` directories, or current working directory for a `*.application.php` file before running any commands.
Pre-set globals using `--set`, `--name=value`, `--config`.

Change starting context using `--cd` (to search elsewhere by default) or adding `--search`.

<?php
$tab = "    ";
foreach ($this->categories as $category => $commands) {
	echo "== $category " . str_repeat("=", 80 - strlen($category)) . "\n";
	foreach ($commands as $command => $info) {
		$extras = "";
		if (array_key_exists($command, $aliases)) {
			$extras = " (aliases: " . implode(", ", to_array($aliases[$command])) . ")";
		}
		echo "$command$extras\n";
		$desc = avalue($info, "desc", $locale("No description provided."));
		$parameters = [];
		// // 		foreach (avalue($info, 'global', array()) as $global => $foo) {
		// // 			$parameters[] = "$global (" . $foo[0] . ")" . (count($foo) > 1 ? "\n" . Text::indent(avalue($foo, 2), 1, true, $tab) : "");
		// // 		}
		$parameters = implode("\n", $parameters);
		if ($parameters) {
			$parameters = "$tab$tab" . $locale("Globals:") . "\n" . Text::indent($parameters, 3, false, $tab) . "\n";
		}
		$desc .= "\n\n";
		echo rtrim(Text::indent($desc, 1, true, $tab)) . "\n$parameters";
	}
	echo "\n";
}
echo "To get command-specific options: zesk command --help\n";
