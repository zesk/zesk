<?php
/**
 * @package zesk
 * @subpackage Locale
 * @author kent
 * @copyright &copy; 2018 Market Acumen, Inc.
 */
namespace zesk\Locale;

use zesk\Application;

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
	function __construct(Application $application, $file, array $phrases) {
		$this->application = $application;
		$this->file = $file;
		$this->phrases = $phrases;
	}

	/**
	 *
	 * @param string $filename
	 * @param array $phrases
	 * @return unknown[]
	 */
	public function translation_file_append($filename, array $phrases) {
		$contents = File::contents($filename, "");
		if (strlen($contents) === 0) {
			$contents = "<?php\n/* This file is automatically generated, copy it into another file to modify. */\n";
		}
		$additional_tt = "";
		$result = array();
		foreach ($phrases as $k => $value) {
			$v = is_string($value) ? $value : str::right($k, ":=", $k);
			$k = str_replace("'", "\\'", $k);
			if (strpos($contents, "\$tt['$k']") === false) {
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
			$contents .= "\n// " . $this->application->request()->url() . "\n";
			$contents .= $additional_tt;
			$contents .= $return;
			file_put_contents($filename, $contents);
		}
		return $result;
	}

	/**
	 * Dump untranslated phrases
	 */
	public function shutdown() {
		if (count($this->locale_phrases) === 0) {
			return;
		}
		$path = $this->option("auto_path");
		if (!$path) {
			return;
		}
		$app = $this->application;
		$formats = arr::change_value_case(to_list($this->option("formats")));
		$do_csv = in_array("csv", $formats);
		if (!$path) {
			$app->logger->warning("No {class}::auto_path specified in {class}::shutdown", array(
				"class" => get_class($this)
			));
			return;
		}
		if (!Directory::is_absolute($path)) {
			$path = $app->path($path);
		}
		if (!is_dir($path)) {
			$app->logger->warning("{class}::auto_path {path} is not a directory", array(
				"path" => $path,
				"class" => get_class($this)
			));
			return;
		}

		$filename = path($path, $this->locale_string . '-auto.inc');
		$csv_append = self::translation_file_append($filename, $this->locale_phrases);
		if (count($csv_append) > 0) {
			$app->logger->debug("{class}::shutdown - Appended {n} entries to {filename}", array(
				"filename" => $filename,
				"n" => count($csv_append),
				"class" => __CLASS__
			));
			if ($do_csv) {
				$csv_filename = path($path, $this->locale_string . '-auto.csv');
				$csv = str::csv_quote_row(array(
					"en_US",
					$this->locale_string
				));
				foreach ($csv_append as $k => $v) {
					$csv .= str::csv_quote_row(array(
						$k,
						$v
					));
				}
				File::append($csv_filename, $csv);
			}
		}
	}
}