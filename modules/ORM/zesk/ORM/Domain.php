<?php
declare(strict_types=1);

namespace zesk\ORM;

use zesk\Application;
use zesk\ArrayTools;
use zesk\Exception_Configuration;
use zesk\Exception_Deprecated;
use zesk\Exception_Directory_NotFound;
use zesk\Exception_File_NotFound;
use zesk\Exception_File_Permission;
use zesk\Exception_Key;
use zesk\Exception_Semantics;
use zesk\Exception_Unimplemented;
use zesk\File;
use zesk\Net\Sync;
use zesk\Paths;
use zesk\Text;

/**
 * @see Class_Domain
 * @author kent
 * @property int $id
 * @property string $name
 * @property string $tld
 */
class Domain extends ORMBase {
	/**
	 *
	 * @todo credit
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
	 * @todo credit
	 * @var string
	 */
	public const url_tlds_by_alpha = 'http://data.iana.org/TLD/tlds-alpha-by-domain.txt';

	/**
	 *
	 * @var array
	 */
	private static array $public_tlds = [];

	/**
	 *
	 * @param Application $application
	 */
	public static function cron_hour(Application $application): void {
		foreach ([
			self::url_public_suffix_list => self::publicSuffixListFile($application->paths),
			self::url_tlds_by_alpha => self::tldByAlphaFile($application->paths),
		] as $url => $path) {
			try {
				Sync::url_to_file($application, $url, $path);
			} catch (Exception_File_Permission|Exception_Directory_NotFound $e) {
				$application->logger->error($e);
			}
		}
	}

	/**
	 * @param Application $application
	 * @return void
	 */
	public static function schema_updated(Application $application): void {
		self::updateDataFiles($application);
	}

	/**
	 *
	 * @param Application $application
	 * @param string $name
	 * @return Domain
	 */
	public static function domainFactory(Application $application, string $name): self {
		$domain = $application->ormFactory(__CLASS__, [
			'name' => $name,
		]);
		return $domain->nameChanged();
	}

	/**
	 * Compute the TLD for a domain
	 *
	 * @return $this
	 */
	protected function nameChanged(): self {
		$this->tld = $this->computeTLD();
		return $this;
	}

	/**
	 * @return $this
	 * @throws Exception_Configuration
	 * @throws Exception_Deprecated
	 * @throws Exception_Key
	 * @throws Exception_ORMNotFound
	 * @throws Exception_ORMDuplicate
	 * @throws Exception_ORMEmpty
	 * @throws Exception_Store
	 * @throws Exception_Semantics
	 * @throws Exception_Unimplemented
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
		$domains = $this->_lazyLoadTLDs();
		$server = $this->name;
		$x = explode('.', strrev(strtolower($server)), 4);
		$last = null;
		while (count($x) >= 3) {
			$last = strrev(array_pop($x));
		}
		$default = strrev(implode('.', $x));
		do {
			$try = strrev(implode('.', $x));
			if (isset($domains[$try])) {
				return "$last.$try";
			}
			$last = strrev(array_pop($x));
		} while (count($x) > 0);
		return $default;
	}

	/**
	 * @throws Exception_File_Permission
	 * @throws Exception_File_NotFound
	 */
	private function _lazyLoadTLDs(): array {
		if (!self::$public_tlds) {
			self::$public_tlds = $this->loadPublicTLDs($this->application);
		}
		return self::$public_tlds;
	}

	/**
	 *
	 * @return string
	 * @throws Exception_File_Permission
	 * @throws Exception_File_NotFound
	 */
	public function computeTLD(): string {
		$domains = $this->_lazyLoadTLDs();
		$server = $this->name;
		$x = explode('.', strrev(strtolower($server)), 4);
		$default = strrev($x[0]);
		while (count($x) >= 2) {
			array_pop($x);
		}
		do {
			$try = strrev(implode('.', $x));
			if (isset($domains[$try])) {
				return "$try";
			}
			array_pop($x);
		} while (count($x) > 0);
		return $default;
	}

	/**
	 * @param Paths $paths
	 * @return string
	 */
	private static function publicSuffixListFile(Paths $paths): string {
		return $paths->zesk('etc/db/public-tlds.txt');
	}

	/**
	 * @param Paths $paths
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
			// TODO Net_Sync::url_to_file($application, $url, $path);
		}
	}

	/**
	 * Load the public TLDs from the file
	 * @throws Exception_File_NotFound|Exception_File_Permission
	 */
	private static function loadPublicTLDs(Application $application): array {
		$contents = strtolower(File::contents(self::publicSuffixListFile($application->paths)));
		$topDomainSuffixList = ArrayTools::listTrimClean(explode("\n", Text::remove_line_comments($contents, '//')));
		return array_change_key_case(ArrayTools::valuesFlipCopy($topDomainSuffixList));
	}
}
