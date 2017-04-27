<?php
namespace zesk;

/**
 * @see Class_Domain
 * @author kent
 * @property id $id
 * @property string $name
 * @property string $tld
 */
class Domain extends Object {
	/**
	 * 
	 * @var string
	 */
	const url_public_suffix_list = "https://publicsuffix.org/list/public_suffix_list.dat";
	
	/**
	 * @see http://www.seobythesea.com/2006/01/googles-most-popular-and-least-popular-top-level-domains/
	 * @var string
	 */
	const default_public_suffix_list_file = "com\norg\nedu\ngov\nuk\nnet\nca\nde\njp\nfr\nau\nus\nru\nch\nit\nnl\nse\nno\nes\nmil";
	/**
	 * 
	 * @var string
	 */
	const url_tlds_by_alpha = "http://data.iana.org/TLD/tlds-alpha-by-domain.txt";
	
	/**
	 * 
	 * @var string[string]
	 */
	private static $public_tlds = null;
	
	/**
	 * 
	 * @param Application $application
	 */
	public static function cron_hour(Application $application) {
		foreach (array(
			self::url_public_suffix_list => self::public_suffix_list_file(),
			self::url_tlds_by_alpha => self::tlds_by_alpha_file()
		) as $url => $path) {
			Net_Sync::url_to_file($url, $path);
		}
	}
	/**
	 * 
	 * @param Command_Database_Schema $command
	 */
	public static function schema_updated(Application $application) {
		self::update_data_files($application);
	}
	
	/**
	 * 
	 * @param string $name
	 * @return \zesk\Domain
	 */
	static function domain_factory($name) {
		$domain = new self(array(
			"name" => $name
		));
		return $domain->name_changed();
	}
	
	protected function name_changed() {
		$this->tld = $this->compute_tld();
		return $this;
	}
	/**
	 * 
	 * {@inheritDoc}
	 * @see \zesk\Object::store()
	 */
	function store() {
		$this->tld = $this->compute_tld();
		return parent::store();
	}
	
	/**
	 * 
	 * @return string
	 */
	function compute_cookie_domain() {
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
	function compute_tld() {
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
	private static function public_suffix_list_file() {
		global $zesk;
		/* @var $zesk Kernel */
		return $zesk->paths->zesk("etc/db/public-tlds.txt");
	}
	
	/**
	 * @return string
	 */
	private static function tlds_by_alpha_file() {
		global $zesk;
		/* @var $zesk Kernel */
		return $zesk->paths->zesk("etc/db/tlds.txt");
	}
	
	/**
	 * Update our data files from our remote URLs
	 */
	private static function update_data_files(Application $application) {
		foreach (array(
			self::url_public_suffix_list => self::public_suffix_list_file(),
			self::url_tlds_by_alpha => self::tlds_by_alpha_file()
		) as $url => $path) {
			Net_Sync::url_to_file($url, $path);
		}
	}
	
	/**
	 * @param $filename File to load
	 * @return string[]
	 * Load the public TLDs from the file
	 */
	private static function load_public_tlds() {
		$contents = strtolower(File::contents(self::public_suffix_list_file(), self::default_public_suffix_list_file));
		self::$public_tlds = arr::flip_copy(arr::trim_clean(explode("\n", Text::remove_line_comments($contents, '//'))));
	}
}