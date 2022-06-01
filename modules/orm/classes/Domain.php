<?php
declare(strict_types=1);

namespace zesk;

/**
 * @see Class_Domain
 * @author kent
 * @property id $id
 * @property string $name
 * @property string $tld
 */
class Domain extends ORM {
	/**
	 *
	 * @var string
	 */
	public const url_public_suffix_list = 'https://publicsuffix.org/list/public_suffix_list.dat';

	/**
	 * @see http://www.seobythesea.com/2006/01/googles-most-popular-and-least-popular-top-level-domains/
	 * @var string
	 */
	public const default_public_suffix_list_file = "com\norg\nedu\ngov\nuk\nnet\nca\nde\njp\nfr\nau\nus\nru\nch\nit\nnl\nse\nno\nes\nmil";

	/**
	 *
	 * @var string
	 */
	public const url_tlds_by_alpha = 'http://data.iana.org/TLD/tlds-alpha-by-domain.txt';

	/**
	 *
	 * @var string[string]
	 */
	private static $public_tlds = null;

	/**
	 *
	 * @param Application $application
	 */
	public static function cron_hour(Application $application): void {
		foreach ([
					 self::url_public_suffix_list => self::publicSuffixListFile($application->paths),
					 self::url_tlds_by_alpha => self::tldByAlphaFile($application->paths),
				 ] as $url => $path) {
			Net_Sync::url_to_file($application, $url, $path);
		}
	}

	/**
	 *
	 * @param Command_ORM_Schema $command
	 */
	public static function schema_updated(Application $application): void {
		self::updateDataFiles($application);
	}

	/**
	 *
	 * @param string $name
	 * @return \zesk\Domain
	 */
	public static function domain_factory(Application $application, string $name): self {
		$domain = $application->orm_factory(__CLASS__, [
			'name' => $name,
		]);
		return $domain->nameChanged();
	}

	/**
	 * Compute the TLD for a domain
	 *
	 * @return \zesk\Domain
	 */
	protected function nameChanged() {
		$this->tld = $this->computeTLD();
		return $this;
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\ORM::store()
	 */
	public function store(): self {
		$this->tld = $this->computeTLD();
		return parent::store();
	}

	/**
	 *
	 * @return string
	 */
	public function computeCookieDomain(): string {
		if (!self::$public_tlds) {
			$this->loadPublicTLDs();
		}
		$server = $this->name;
		$x = explode('.', strrev(strtolower($server)), 4);
		$last = null;
		while (count($x) >= 3) {
			$last = strrev(array_pop($x));
		}
		$default = strrev(implode('.', $x));
		do {
			$try = strrev(implode('.', $x));
			if (isset(self::$public_tlds[$try])) {
				return "$last.$try";
			}
			$last = strrev(array_pop($x));
		} while (count($x) > 0);
		return $default;
	}

	/**
	 *
	 * @return string
	 */
	public function computeTLD(): string {
		if (!self::$public_tlds) {
			$this->loadPublicTLDs();
		}
		$server = $this->name;
		$x = explode('.', strrev(strtolower($server)), 4);
		$default = strrev($x[0]);
		while (count($x) >= 2) {
			array_pop($x);
		}
		do {
			$try = strrev(implode('.', $x));
			if (isset(self::$public_tlds[$try])) {
				return "$try";
			}
			array_pop($x);
		} while (count($x) > 0);
		return $default;
	}

	/**
	 * @return string
	 */
	private static function publicSuffixListFile(Paths $paths): string {
		return $paths->zesk('etc/db/public-tlds.txt');
	}

	/**
	 * @return string
	 */
	private static function tldByAlphaFile(Paths $paths): string {
		return $paths->zesk('etc/db/tlds.txt');
	}

	/**
	 * Update our data files from our remote URLs
	 */
	private static function updateDataFiles(Application $application): void {
		foreach ([
					 self::url_public_suffix_list => self::publicSuffixListFile($application->paths),
					 self::url_tlds_by_alpha => self::tldByAlphaFile($application->paths),
				 ] as $url => $path) {
			Net_Sync::url_to_file($application, $url, $path);
		}
	}

	/**
	 * @param $filename File to load
	 * @return string[]
	 * Load the public TLDs from the file
	 */
	private function loadPublicTLDs(): void {
		$contents = strtolower(File::contents(self::publicSuffixListFile($this->application->paths)));
		self::$public_tlds = ArrayTools::valuesFlipCopy(ArrayTools::listTrimClean(explode("\n", Text::remove_line_comments($contents, '//'))));
	}
}
