<?php

class Command_Locale_Export extends Command_Base {

	public $option_types = array(
		'source' => 'string',
		'destination' => 'string',
		'no-exclude' => 'boolean'
	);

	public $option_help = array(
		'source' => 'Source file to read (include file which returns an array of phrase => translation',
		'destination' => 'CSV file to export to',
		'no-exclude' => 'Do not exclude database values which have been deleted already'
	);

	protected function run() {
		$source_language_file = $this->option("language-file", zesk::get("Module_PolyGlot::source_file"));
		if (!$source_language_file) {
			$this->usage("Need a source --language-file to determine source strings");
		}
		$destination = $this->option("destination", "locale.csv");
		if (!$destination) {
			$this->usage("Need a file --destination {destination}");
		}
		$exclusions = $this->option_bool("no-exclude") ? array() : $this->load_exclusions();
		$source_locale = zesk::load($source_language_file) + zesk\Locale::load("en_US");
		$csv = new CSV_Writer();
		$csv->file($destination);
		$csv->set_headers(array(
			"phrase",
			"translation"
		));
		$n_excluded = $n_written = 0;
		foreach ($source_locale as $phrase => $translation) {
			if (array_key_exists($phrase, $exclusions)) {
				$n_excluded++;
				continue;
			}
			$csv->set_row(array(
				$phrase,
				$translation
			));
			$csv->write_row();
			++$n_written;
		}
		$csv->close();
		$this->log("Wrote {destination} {n_written} {rows}, excluded {n_excluded} {phrases}.", compact("destination") + array(
			"n_written" => $n_written,
			"rows" => zesk\Locale::plural("row", $n_written),
			"n_excluded" => $n_excluded,
			"phrases" => zesk\Locale::plural("phrase", $n_excluded)
		));
		return 0;
	}

	protected function load_exclusions() {
		$column = "original";
		$exclusions = Object::class_query("PolyGlot_Token")->where("status", "delete")->what(array(
			$column => $column
		))->to_array($column, $column);
		return $exclusions;
	}
}