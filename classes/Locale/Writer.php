<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage Locale
 * @author kent
 * @copyright &copy; 2022 Market Acumen, Inc.
 */
namespace zesk\Locale;

use zesk\Application;
use zesk\File;
use zesk\StringTools;
use zesk\Exception_Unimplemented;

/**
 * @author kent
 */
class Writer {
	/**
	 *
	 * @var Application
	 */
	private $application = null;

	/**
	 *
	 * @param Application $application
	 * @param unknown $file
	 * @param array $phrases
	 */
	public function __construct(Application $application, $file) {
		$this->application = $application;
		$this->file = $file;
	}

	/**
	 *
	 * @param string $filename
	 * @param array $phrases
	 * @param string $context Some context for where these strings came from
	 * @return unknown[]
	 */
	public function append(array $phrases, $context = null) {
		$extension = File::extension($this->file);
		$method = $extension . '_append';
		if (method_exists($this, $method)) {
			return $this->$method($phrases, $context = null);
		}

		throw new Exception_Unimplemented("{method}: No handler for file extension {extension} (file is {file})", [
			"method" => __METHOD__,
			"extension" => $extension,
			"file" => $this->file,
		]);
	}

	/**
	 *
	 * @param array $phrases
	 * @return string[]
	 */
	protected function php_append(array $phrases, $context = null) {
		$contents = File::contents($this->file, "");
		if (strlen($contents) === 0) {
			$contents = "<?php\n/* This file is automatically generated, copy it into another file to modify. */\n";
		}
		$additional_tt = "";
		$result = [];
		foreach ($phrases as $k => $value) {
			$v = is_string($value) ? $value : StringTools::right($k, ":=", $k);
			$k = str_replace("'", "\\'", $k);
			if (!str_contains($contents, "\$tt['$k']")) {
				$v = str_replace("'", "\\'", $v);
				$additional_tt .= "\$tt['$k'] = '$v';\n";
				$result[$k] = $value;
			}
		}
		if ($additional_tt !== "") {
			$return = "\nreturn \$tt;\n";
			if (strpos($contents, $return)) {
				$contents = str_replace($return, "", $contents);
			}
			if ($context) {
				$contents .= "\n// " . $context . "\n";
			}
			$contents .= $additional_tt;
			$contents .= $return;
			file_put_contents($this->file, $contents);
		}
		return $result;
	}

	/**
	 * Dump untranslated phrases
	 */
	protected function csv_append(array $phrases) {
		$app = $this->application;
		if (count($phrases) === 0) {
			return [];
		}
		$csv = StringTools::csv_quote_row([
			"en_US",
			$this->locale_string,
		]);
		foreach ($phrases as $k => $v) {
			$csv .= StringTools::csv_quote_row([
				$k,
				$v,
			]);
			$result[$k] = $v;
		}
		File::append($this->file, $csv);
		$app->logger->debug("{method} - Appended {n} entries to {filename}", [
			"filename" => $this->file,
			"n" => count($phrases),
			"method" => __METHOD__,
		]);
		return $result;
	}
}
