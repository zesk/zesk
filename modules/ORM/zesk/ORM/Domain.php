<?php
declare(strict_types=1);

namespace zesk\ORM;

use zesk\Application;
use zesk\Application\Paths;
use zesk\ArrayTools;
use zesk\Database\Exception\SQLException;
use zesk\Exception\ClassNotFound;
use zesk\Exception\ConfigurationException;
use zesk\Exception\DirectoryNotFound;
use zesk\Exception\FileNotFound;
use zesk\Exception\FilePermission;
use zesk\Exception\KeyNotFound;
use zesk\Exception\ParameterException;
use zesk\Exception\ParseException;
use zesk\Exception\Semantics;
use zesk\File;
use zesk\Net\Sync;
use zesk\ORM\Exception\ORMEmpty;
use zesk\ORM\Exception\ORMNotFound;
use zesk\ORM\Interface\SchemaUpdatedInterface;
use zesk\Text;

/**
 * @see Class_Domain
 * @author kent
 * @property int $id
 * @property string $name
 * @property string $tld
 */
class Domain extends ORMBase implements SchemaUpdatedInterface {
	/**
	 *
	 * @todo credit
	 * @var string
	 */
	public const URL_PUBLIC_SUFFIX_LIST = 'https://publicsuffix.org/list/public_suffix_list.dat';

	/**
	 * @see http://www.seobythesea.com/2006/01/googles-most-popular-and-least-popular-top-level-domains/
	 * @var string
	 */
	public const DEFAULT_PUBLIC_SUFFIX_LIST = "com\norg\nedu\ngov\nuk\nnet\nca\nde\njp\nfr\nau\nus\nru\nch\nit\nnl\nse\nno\nes\nmil";

	/**
	 *
	 * @todo credit
	 * @var string
	 */
	public const URL_TOP_LEVEL_DOMAINS = 'https://data.iana.org/TLD/tlds-alpha-by-domain.txt';

	/**
	 *
	 * @var array
	 */
	private static array $publicTopLevelDomains = [];

	/**
	 *
	 * @param Application $application
	 */
	public static function cron_hour(Application $application): void {
		foreach ([
			self::URL_PUBLIC_SUFFIX_LIST => self::publicSuffixListFile($application->paths),
			self::URL_TOP_LEVEL_DOMAINS => self::topLevelDomainsFile($application->paths),
		] as $url => $path) {
			try {
				Sync::urlToFile($application, $url, $path);
			} catch (FilePermission|DirectoryNotFound $e) {
				$application->logger->error($e);
			}
		}
	}

	/**
	 * @return void
	 * @throws DirectoryNotFound
	 * @throws FilePermission
	 */
	public function hook_schema_updated(): void {
		self::updateDataFiles($this->application);
	}

	/**
	 *
	 * @param Application $application
	 * @param string $name
	 * @return Domain
	 * @throws FileNotFound
	 * @throws FilePermission
	 */
	public static function domainFactory(Application $application, string $name): self {
		$domain = $application->ormFactory(__CLASS__, [
			'name' => $name,
		]);
		assert($domain instanceof self);
		return $domain->nameChanged();
	}

	/**
	 * Compute the TLD for a domain
	 *
	 * @return $this
	 * @throws FileNotFound
	 * @throws FilePermission
	 */
	protected function nameChanged(): self {
		$this->tld = $this->computeTLD();
		return $this;
	}

	/**
	 * @return $this
	 * @throws ClassNotFound
	 * @throws ConfigurationException
	 * @throws Exception\ORMDuplicate
	 * @throws Exception\StoreException
	 * @throws FileNotFound
	 * @throws FilePermission
	 * @throws ORMEmpty
	 * @throws ORMNotFound
	 * @throws ParameterException
	 * @throws ParseException
	 * @throws Semantics
	 * @throws SQLException
	 * @throws KeyNotFound
	 */
	public function store(): self {
		$this->tld = $this->computeTLD();
		return parent::store();
	}

	/**
	 *
	 * @return string
	 * @throws FileNotFound
	 * @throws FilePermission
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
	 * @throws FilePermission
	 * @throws FileNotFound
	 */
	private function _lazyLoadTLDs(): array {
		if (!self::$publicTopLevelDomains) {
			self::$publicTopLevelDomains = $this->loadPublicTLDs($this->application);
		}
		return self::$publicTopLevelDomains;
	}

	/**
	 *
	 * @return string
	 * @throws FilePermission
	 * @throws FileNotFound
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
	private static function topLevelDomainsFile(Paths $paths): string {
		return $paths->zesk('etc/db/tlds.txt');
	}

	/**
	 * Update our data files from our remote URLs
	 *
	 * @param Application $application
	 * @return void
	 * @throws DirectoryNotFound
	 * @throws FilePermission
	 */
	private static function updateDataFiles(Application $application): void {
		foreach ([
			self::URL_PUBLIC_SUFFIX_LIST => self::publicSuffixListFile($application->paths),
			self::URL_TOP_LEVEL_DOMAINS => self::topLevelDomainsFile($application->paths),
		] as $url => $path) {
			Sync::urlToFile($application, $url, $path);
		}
	}

	/**
	 * Load the public TLDs from the file
	 * @throws FileNotFound|FilePermission
	 */
	private static function loadPublicTLDs(Application $application): array {
		$contents = strtolower(File::contents(self::publicSuffixListFile($application->paths)));
		$topDomainSuffixList = ArrayTools::listTrimClean(explode("\n", Text::removeLineComments($contents, '//')));
		return array_change_key_case(ArrayTools::valuesFlipCopy($topDomainSuffixList));
	}
}
