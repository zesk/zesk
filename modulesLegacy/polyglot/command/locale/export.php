<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

use Reader;

/**
 *
 * @author kent
 *
 */
class Command_Locale_Export extends Command_Base {
	public array $option_types = [
		'source' => 'string',
		'destination' => 'string',
		'no-exclude' => 'boolean',
	];

	public $option_help = [
		'source' => 'Source file to read (include file which returns an array of phrase => translation',
		'destination' => 'CSV file to export to',
		'no-exclude' => 'Do not exclude database values which have been deleted already',
	];

	protected function run() {
		$source_language_file = $this->option('language-file', $this->application->configuration->getPath('zesk\\Module_PolyGlot::source_file'));
		if (!$source_language_file) {
			$this->usage('Need a source --language-file to determine source strings');
		}
		$destination = $this->option('destination', 'locale.csv');
		if (!$destination) {
			$this->usage('Need a file --destination {destination}');
		}
		$exclusions = $this->optionBool('no-exclude') ? [] : $this->load_exclusions();
		$source_locale = $this->application->load($source_language_file) + Reader::factory($this->application->localePath(), 'en_US')->execute();
		$csv = new CSV_Writer();
		$csv->file($destination);
		$csv->set_headers([
			'phrase',
			'translation',
		]);
		$n_excluded = $n_written = 0;
		foreach ($source_locale as $phrase => $translation) {
			if (array_key_exists($phrase, $exclusions)) {
				$n_excluded++;

				continue;
			}
			$csv->set_row([
				$phrase,
				$translation,
			]);
			$csv->write_row();
			++$n_written;
		}
		$csv->close();
		$this->log('Wrote {destination} {n_written} {rows}, excluded {n_excluded} {phrases}.', compact('destination') + [
			'n_written' => $n_written,
			'rows' => $this->application->locale->plural('row', $n_written),
			'n_excluded' => $n_excluded,
			'phrases' => $this->application->locale->plural('phrase', $n_excluded),
		]);
		return 0;
	}

	protected function load_exclusions() {
		$column = 'original';
		$exclusions = $this->application->ormRegistry('PolyGlot_Token')
			->querySelect()
			->addWhere('status', 'delete')
			->addWhatIterable([
				$column => $column,
			])
			->toArray($column, $column);
		return $exclusions;
	}
}
