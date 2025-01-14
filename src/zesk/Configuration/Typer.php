<?php
declare(strict_types=1);

namespace zesk\Configuration;

use zesk\File;


class Typer {
	public function determineHandler(string $file, string $content): string
	{
		$handler = File::extension($file);
		if (Parser::exists($handler)) {
			return $handler;
		}
		if (str_contains($file, ".env.")) {
			return "conf";
		}
		if (str_starts_with($content, "#!/")) {
			return "conf";
		}
		return "";
	}
}
