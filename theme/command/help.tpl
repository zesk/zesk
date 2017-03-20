<?php
/**
 * @copyright &copy; 2016 Market Acumen, Inc.
 */
namespace zesk;

if (false) {
	/* @var $this Template */
	
	$zesk = $this->zesk;
	/* @var $zesk \zesk\Kernel */
	
	$application = $this->application;
	/* @var $application \zesk\Application */
	
	$session = $this->session;
	/* @var $session \zesk\Session */
	
	$router = $this->router;
	/* @var $request \zesk\Router */
	
	$request = $this->request;
	/* @var $request \zesk\Request */
	
	$response = $this->response;
	/* @var $response \zesk\Response_Text_HTML */
}

$aliases = to_array($this->aliases);
$aliases = arr::flip_multiple($aliases);

?># Zesk Command

    zesk [ --set name=value ] [ --name=value ] [ --unset name ] [ --cd dir ] [ --search dir ] [ --config file ] command0 [ command1 ... ]

Zesk command-line management interface. Execute commands within an application context.
Commands may process arguments differently.

Searches from `--search` directories, or current working directory for a `*.application.inc` file before running any commands.
Pre-set globals using `--set`, `--name=value`, `--config`.

Change starting context using `--cd` (to search elsewhere by default) or adding `--search`.

<?php
$tab = "    ";
foreach ($this->categories as $category => $commands) {
	echo "== $category " . str_repeat("=", 80 - strlen($category)) . "\n";
	foreach ($commands as $command => $info) {
		$extras = "";
		if (array_key_exists($command, $aliases)) {
			$extras = " (aliases: " . implode($aliases[$command], ", ") . ")";
		}
		echo "$tab$command$extras\n";
		$desc = avalue($info, "desc", __("No description provided."));
		$parameters = array();
		foreach (avalue($info, 'global', array()) as $global => $foo) {
			$parameters[] = "$global (" . $foo[0] . ")" . "\n" . Text::indent($foo[2], 1, true, $tab);
		}
		$parameters = implode("\n", $parameters);
		if ($parameters) {
			$parameters = "$tab$tab" . __("Globals:") . "\n" . Text::indent($parameters, 3, false, $tab) . "\n";
		}
		$desc .= "\n\n";
		echo rtrim(Text::indent($desc, 2, true, $tab)) . "\n$parameters";
	}
	echo "\n";
}
