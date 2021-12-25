<?php declare(strict_types=1);
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
	public const url_public_suffix_list = "https://publicsuffix.org/list/public_suffix_list.dat";

	/**
	 * @see http://www.seobythesea.com/2006/01/googles-most-popular-and-least-popular-top-level-domains/
	 * @var string
	 */
	public const default_public_suffix_list_file = "com\norg\nedu\ngov\nuk\nnet\nca\nde\njp\nfr\nau\nus\nru\nch\nit\nnl\nse\nno\nes\nmil";

	/**
	 *
	 * @var string
	 */
	public const url_tlds_by_alpha = "http://data.iana.org/TLD/tlds-alpha-by-domain.txt";

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
			self::url_public_suffix_list => self::public_suffix_list_file($application->paths),
			self::url_tlds_by_alpha => self::tlds_by_alpha_file($application->paths),
		] as $url => $path) {
			Net_Sync::url_to_file($application, $url, $path);
		}
	}

	/**
	 *
	 * @param Command_ORM_Schema $command
	 */
	public static function schema_updated(Application $application): void {
		self::update_data_files($application);
	}

	/**
	 *
	 * @param string $name
	 * @return \zesk\Domain
	 */
	public static function domain_factory(Application $application, $name) {
		$domain = $application->orm_factory(__CLASS__, [
			"name" => $name,
		]);
		return $domain->name_changed();
	}

	/**
	 * Compute the TLD for a domain
	 *
	 * @return \zesk\Domain
	 */
	protected function name_changed() {
		$this->tld = $this->compute_tld();
		return $this;
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\ORM::store()
	 */
	public function store() {
		$this->tld = $this->compute_tld();
		return parent::store();
	}

	/**
	 *
	 * @return string
	 */
	public function compute_cookie_domain() {
		if (!self::$public_tlds) {
			$this->load_public_tlds();
		}
		$server = $this->name;
		$x = explode(".", strrev(strtolower($server)), 4);
		$last = null;
		while (count($x) >= 3) {
			$last = strrev(array_pop($x));
		}
		$default = strrev(implode(".", $x));
		do {
			$try = strrev(implode(".", $x));
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
	public function compute_tld() {
		if (!self::$public_tlds) {
			$this->load_public_tlds();
		}
		$server = $this->name;
		$x = explode(".", strrev(strtolower($server)), 4);
		$default = strrev($x[0]);
		while (count($x) >= 2) {
			array_pop($x);
		}
		do {
			$try = strrev(implode(".", $x));
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
	private static function public_suffix_list_file(Paths $paths) {
		return $paths->zesk("etc/db/public-tlds.txt");
	}

	/**
	 * @return string
	 */
	private static function tlds_by_alpha_file(Paths $paths) {
		return $paths->zesk("etc/db/tlds.txt");
	}

	/**
	 * Update our data files from our remote URLs
	 */
	private static function update_data_files(Application $application): void {
		foreach ([
			self::url_public_suffix_list => self::public_suffix_list_file($application->paths),
			self::url_tlds_by_alpha => self::tlds_by_alpha_file($application->paths),
		] as $url => $path) {
			Net_Sync::url_to_file($application, $url, $path);
		}
	}

	/**
	 * @param $filename File to load
	 * @return string[]
	 * Load the public TLDs from the file
	 */
	private function load_public_tlds() {
		$contents = strtolower(File::contents(self::public_suffix_list_file($this->application->paths), self::default_public_suffix_list_file));
		self::$public_tlds = ArrayTools::flip_copy(ArrayTools::trim_clean(explode("\n", Text::remove_line_comments($contents, '//'))));
	}
}
