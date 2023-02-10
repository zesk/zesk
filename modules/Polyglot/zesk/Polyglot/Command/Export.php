<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk\Polyglot\Command;

use zesk\Locale\Reader as LocaleReader;
use zesk\Command_Base;
use zesk\CSV\Writer as CSV_Writer;
use zesk\Polyglot\Module;
use zesk\Polyglot\Token;

/**
 *
 * @author kent
 *
 */
class Export extends Command_Base {
	protected array $shortcuts = ['polyglot-export'];

	public array $option_types = [
		'source' => 'string', 'destination' => 'string', 'no-exclude' => 'boolean',
	];

	public array $option_help = [
		'source' => 'Source file to read (include file which returns an array of phrase => translation',
		'destination' => 'CSV file to export to',
		'no-exclude' => 'Do not exclude database values which have been deleted already',
	];

	protected function run(): int {
		$source_language_file = $this->option('language-file', $this->application->configuration->getPath([
			Module::class, 'sourceFile',
		]));
		if (!$source_language_file) {
			$this->usage('Need a source --language-file to determine source strings');
		}
		$destination = $this->option('destination', 'locale.csv');
		if (!$destination) {
			$this->usage('Need a file --destination {destination}');
		}
		$exclusions = $this->optionBool('no-exclude') ? [] : $this->loadExclusions();

		$source_locale = $this->application->load($source_language_file) + LocaleReader::factory($this->application->localePath(), 'en_US')->execute();
		$csv = new CSV_Writer();
		$csv->setFile($destination);
		$csv->setHeaders([
			'phrase', 'translation',
		]);
		$n_excluded = $n_written = 0;
		foreach ($source_locale as $phrase => $translation) {
			if (array_key_exists($phrase, $exclusions)) {
				$n_excluded++;

				continue;
			}
			$csv->setRow([
				$phrase, $translation,
			]);
			$csv->writeRow();
			++$n_written;
		}
		$csv->close();
		$this->log('Wrote {destination} {n_written} {rows}, excluded {n_excluded} {phrases}.', compact('destination') + [
			'n_written' => $n_written, 'rows' => $this->application->locale->plural('row', $n_written),
			'n_excluded' => $n_excluded, 'phrases' => $this->application->locale->plural('phrase', $n_excluded),
		]);
		return 0;
	}

	protected function loadExclusions(): array {
		$column = 'original';
		return $this->application->ormRegistry(Token::class)->querySelect()->addWhere('status', 'delete')->appendWhat([
			'key' => $column, 'exists' => 1,
		])->toArray('key', 'exists');
	}
}
