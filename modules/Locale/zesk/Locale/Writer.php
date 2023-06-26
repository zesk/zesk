<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage Locale
 * @author kent
 * @copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Locale;

use zesk\Application;
use zesk\Exception\FileNotFound;
use zesk\Exception\FilePermission;
use zesk\Exception\UnimplementedException;
use zesk\File;
use zesk\StringTools;

/**
 * @author kent
 */
class Writer {
	/**
	 *
	 * @var Application
	 */
	private Application $application;

	/**
	 * @var string
	 */
	private string $file;

	/**
	 * @var string
	 */
	private string $localeHeader;

	/**
	 * @param Application $application
	 * @param string $file
	 * @param string $localeHeader
	 */
	public function __construct(Application $application, string $file, string $localeHeader) {
		$this->application = $application;
		$this->file = $file;
		$this->localeHeader = $localeHeader;
	}

	/**
	 *
	 * @param array $phrases
	 * @param string $context Some context for where these strings came from
	 * @return array
	 * @throws FileNotFound
	 * @throws FilePermission
	 * @throws UnimplementedException
	 */
	public function append(array $phrases, string $context = ''): array {
		$extension = File::extension($this->file);
		return match ($extension) {
			'php' => $this->appendPHPFile($phrases, $context),
			'json' => $this->appendCSVFile($phrases, $context),
			default => throw new UnimplementedException('{method}: No handler for file extension {extension} (file is {file})', [
				'method' => __METHOD__, 'extension' => $extension, 'file' => $this->file,
			])
		};
	}

	/**
	 *
	 * @param array $phrases
	 * @return string[]
	 */
	/**
	 * @param array $phrases
	 * @param string $context
	 * @return array
	 * @throws FilePermission
	 * @throws FileNotFound
	 */
	protected function appendPHPFile(array $phrases, string $context = ''): array {
		$contents = File::contents($this->file);
		if (strlen($contents) === 0) {
			$contents = '<';
			$contents .= "?php\n";
			$contents .= "/*\n";
			$contents .= ' * Locale: ' . $this->localeHeader . "\n";
			$contents .= " * This file is automatically generated, copy it into another file to modify.\n";
			$contents .= " */\n";
		}
		$additional_tt = '';
		$result = [];
		foreach ($phrases as $k => $value) {
			$v = is_string($value) ? $value : StringTools::right($k, ':=', $k);
			$k = str_replace('\'', '\\\'', $k);
			if (!str_contains($contents, "\$tt['$k']")) {
				$v = str_replace('\'', '\\\'', $v);
				$additional_tt .= "\$tt['$k'] = '$v';\n";
				$result[$k] = $value;
			}
		}
		if ($additional_tt !== '') {
			$return = "\nreturn \$tt;\n";
			if (strpos($contents, $return)) {
				$contents = str_replace($return, '', $contents);
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
	 *
	 * @param array $phrases
	 * @param string $context
	 * @return array
	 * @throws FilePermission
	 */
	protected function appendCSVFile(array $phrases, string $context): array {
		$app = $this->application;
		if (count($phrases) === 0) {
			return [];
		}
		$csv = StringTools::csvQuoteRow([
			'en_US', $this->localeHeader, 'context',
		]);
		$result = [];
		foreach ($phrases as $k => $v) {
			$csv .= StringTools::csvQuoteRow([
				$k, $v, $context,
			]);
			$result[$k] = $v;
		}
		File::append($this->file, $csv);
		$app->debug('{method} - Appended {n} entries to {filename}', [
			'filename' => $this->file, 'n' => count($phrases), 'method' => __METHOD__,
		]);
		return $result;
	}
}
